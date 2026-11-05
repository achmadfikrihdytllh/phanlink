import {
  Component,
  DestroyRef,
  ElementRef,
  OnDestroy,
  OnInit,
  computed,
  effect,
  inject,
  signal,
  viewChild,
} from '@angular/core';
import { FormsModule } from '@angular/forms';
import { DatePipe } from '@angular/common';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';

import { AuthService } from '../../core/services/auth';
import { ConversationService } from '../../core/services/conversation';
import { EchoService } from '../../core/services/echo';
import { MessageService } from '../../core/services/message';
import { Conversation } from '../../core/models/conversation';
import { Message, MessageAttachment } from '../../core/models/message';
import { PmIcon } from '../../shared/icon/icon';
import { PageFrame } from '../../shared/page-frame/page-frame';

@Component({
  selector: 'app-messages',
  imports: [FormsModule, DatePipe, PmIcon, PageFrame],
  templateUrl: './messages.html',
  styleUrl: './messages.scss',
})
export class Messages implements OnInit, OnDestroy {
  private conversationService = inject(ConversationService);
  private messageService = inject(MessageService);
  private echo = inject(EchoService);
  private auth = inject(AuthService);
  private destroyRef = inject(DestroyRef);

  private feed = viewChild<ElementRef<HTMLElement>>('feed');

  conversations = signal<Conversation[]>([]);
  selected = signal<Conversation | null>(null);
  messages = signal<Message[]>([]);
  draft = signal('');
  query = signal('');
  loading = signal(false);

  currentUserId = this.auth.currentUser()?.id ?? 0;

  filtered = computed(() => {
    const q = this.query().trim().toLowerCase();
    if (!q) return this.conversations();
    return this.conversations().filter((c) => (c.name ?? '').toLowerCase().includes(q));
  });

  /** Files seen in the loaded thread, shown in the "Shared intel" panel. */
  sharedFiles = computed<MessageAttachment[]>(() =>
    this.messages()
      .flatMap((m) => m.attachments ?? [])
      .slice(-4)
      .reverse(),
  );

  onlineCount = computed(
    () => this.selected()?.participants.filter((p) => p.status === 'online').length ?? 0,
  );

  private activeConversationId: number | null = null;

  constructor() {
    // Keep the newest message in view whenever the thread changes.
    effect(() => {
      this.messages();
      queueMicrotask(() => {
        const el = this.feed()?.nativeElement;
        if (el) el.scrollTop = el.scrollHeight;
      });
    });
  }

  ngOnInit(): void {
    this.conversationService
      .list()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          this.conversations.set(res.conversations);
          if (res.conversations.length) this.open(res.conversations[0]);
        },
      });
  }

  ngOnDestroy(): void {
    if (this.activeConversationId) this.echo.leaveConversation(this.activeConversationId);
  }

  open(conversation: Conversation): void {
    if (this.activeConversationId) this.echo.leaveConversation(this.activeConversationId);

    this.activeConversationId = conversation.id;
    this.selected.set(conversation);
    this.messages.set([]);
    this.loading.set(true);

    this.messageService
      .getMessages(conversation.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (res) => {
          // API returns newest first; the feed reads oldest first.
          this.messages.set([...res.messages].reverse());
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });

    this.messageService.markAsRead(conversation.id).subscribe();

    this.echo.listenToConversation(conversation.id, ({ message }: { message: Message }) => {
      this.messages.update((list) => [...list, message]);
      this.messageService.markAsRead(conversation.id).subscribe();
    });
  }

  send(): void {
    const body = this.draft().trim();
    const id = this.selected()?.id;
    if (!body || !id) return;

    this.draft.set('');
    this.messageService.sendText(id, body).subscribe({
      next: (res) => {
        this.messages.update((list) => [...list, res.data]);
        this.messageService.markAsRead(id).subscribe();
      },
      // Put the text back so a failed send is never silently lost.
      error: () => this.draft.set(body),
    });
  }

  isMine(m: Message): boolean {
    return m.sender?.id === this.currentUserId;
  }

  initial(name: string | null | undefined): string {
    return (name ?? '?').charAt(0).toUpperCase();
  }

  previewOf(c: Conversation): string {
    return c.latest_message?.body ?? 'No messages yet';
  }

  fileName(path: string): string {
    return path.split('/').pop() ?? path;
  }

  size(bytes: number): string {
    return bytes >= 1_048_576
      ? `${(bytes / 1_048_576).toFixed(1)} MB`
      : `${Math.max(1, Math.round(bytes / 1024))} KB`;
  }
}
