import {
  Component,
  OnInit,
  OnDestroy,
  AfterViewInit,
  ElementRef,
  ViewChild,
  ViewChildren,
  QueryList,
  signal,
  effect,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Subscription } from 'rxjs';

import { ConversationService } from '../../core/services/conversation';
import { MessageService } from '../../core/services/message';
import { EchoService } from '../../core/services/echo';
import { AuthService } from '../../core/services/auth';

import { Conversation } from '../../core/models/conversation';
import { Message } from '../../core/models/message';

interface Connector {
  key: string;
  d: string;
}

@Component({
  selector: 'app-chat-layout',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './chat-layout.html',
  styleUrls: ['./chat-layout.scss'],
})
export class ChatLayout implements OnInit, AfterViewInit, OnDestroy {

  @ViewChild('feedInner')
  feedInner!: ElementRef<HTMLDivElement>;

  @ViewChildren('bubbleEl')
  bubbleEls!: QueryList<ElementRef<HTMLDivElement>>;

  conversations = signal<Conversation[]>([]);
  selectedConversation = signal<Conversation | null>(null);
  messages = signal<Message[]>([]);
  connectors = signal<Connector[]>([]);

  newMessageText = signal('');

  currentUserId = 0;

  private currentConversationId: number | null = null;

  private resizeObserver?: ResizeObserver;

  private bubbleChanges?: Subscription;

  constructor(
    private conversationService: ConversationService,
    private messageService: MessageService,
    private echoService: EchoService,
    private authService: AuthService
  ) {

    this.currentUserId = this.authService.currentUser()?.id ?? 0;

    effect(() => {

      this.messages();

      queueMicrotask(() => {

        this.scrollBottom();

        this.recalculateLine();

      });

    });

  }

  ngOnInit(): void {

    this.loadConversations();

  }

  ngAfterViewInit(): void {

    this.resizeObserver = new ResizeObserver(() => {

      this.recalculateLine();

    });

    this.resizeObserver.observe(this.feedInner.nativeElement);

    this.bubbleChanges = this.bubbleEls.changes.subscribe(() => {

      requestAnimationFrame(() => {

        this.scrollBottom();

        this.recalculateLine();

      });

    });

    window.addEventListener('resize', this.onResize);

  }

  ngOnDestroy(): void {

    window.removeEventListener('resize', this.onResize);

    this.resizeObserver?.disconnect();

    this.bubbleChanges?.unsubscribe();

    if (this.currentConversationId) {

      this.echoService.leaveConversation(this.currentConversationId);

    }

  }

  private onResize = () => {

    this.recalculateLine();

  };

  loadConversations(): void {

    this.conversationService.list().subscribe({

      next: (res: { conversations: Conversation[] }) => {

        this.conversations.set(res.conversations);

      }

    });

  }

  selectConversation(conversation: Conversation): void {

    if (this.currentConversationId) {

      this.echoService.leaveConversation(this.currentConversationId);

    }

    this.selectedConversation.set(conversation);

    this.currentConversationId = conversation.id;

    this.messages.set([]);

    this.connectors.set([]);

    this.messageService.getMessages(conversation.id).subscribe({

        next: (res) => {
          this.messages.set([...res.messages].reverse());  

        queueMicrotask(() => {

          this.scrollBottom();

          this.recalculateLine();

        });

      }

    });

    this.messageService.markAsRead(conversation.id).subscribe();

    this.echoService.listenToConversation(
      conversation.id,
      ({ message }: { message: Message }) => {

        this.messages.update(list => [...list, message]);

        this.messageService.markAsRead(conversation.id).subscribe();

      }
    );

  }

  get newMessageTextModel(): string {

    return this.newMessageText();

  }

  set newMessageTextModel(value: string) {

    this.newMessageText.set(value);

  }

  isMyMessage(message: Message): boolean {

    return message.sender?.id === this.currentUserId;

  }

  getOtherParticipantInitial(): string {

    return this.selectedConversation()?.name?.charAt(0).toUpperCase() ?? '?';

  }

  sendMessage(): void {

    const body = this.newMessageText().trim();

    const conversationId = this.selectedConversation()?.id;

    if (!body || !conversationId) {

      return;

    }

    this.messageService.sendText(conversationId, body).subscribe({

      next: (res: { data: Message }) => {

        this.messages.update(list => [...list, res.data]);

        this.messageService.markAsRead(conversationId).subscribe();

      }

    });

    this.newMessageText.set('');

  }

  scrollBottom(): void {

    const el = this.feedInner?.nativeElement.closest('.feed') as HTMLElement | null;

    if (!el) {

      return;

    }

    el.scrollTop = el.scrollHeight;

  }

  recalculateLine(): void {

    if (!this.feedInner || !this.bubbleEls || this.bubbleEls.length < 2) {

      this.connectors.set([]);

      return;

    }

    // Base coordinates on #feedInner (.message-stack), not on .feed
    // itself: .feed's own box stays fixed on screen while you scroll
    // (only its content moves), but .message-stack and the absolutely
    // positioned .connector-layer both scroll together, so measuring
    // against .message-stack keeps the line in sync with the bubbles
    // at any scroll position. (.feed has zero padding for this reason —
    // see background.scss — so this lines up with the svg's origin.)
    const containerEl = this.feedInner.nativeElement;

    const containerRect = containerEl.getBoundingClientRect();

    const points = this.bubbleEls.map(ref => {

      const rect = ref.nativeElement.getBoundingClientRect();

      return {
        x: rect.left + rect.width / 2 - containerRect.left,
        y: rect.top + rect.height / 2 - containerRect.top,
      };

    });

    const segments: Connector[] = [];

    for (let i = 0; i < points.length - 1; i++) {

      const a = points[i];
      const b = points[i + 1];

      segments.push({
        key: `${i}-${i + 1}`,
        d: `M ${a.x} ${a.y} L ${b.x} ${b.y}`,
      });

    }

    this.connectors.set(segments);

  }

}