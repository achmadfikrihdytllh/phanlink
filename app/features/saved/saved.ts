import { Component, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { COLLECTIONS, SAVED, SavedMessage } from '../../core/mock/phantom-data';
import { PmIcon } from '../../shared/icon/icon';
import { PageFrame } from '../../shared/page-frame/page-frame';

type Tab = 'all' | 'messages' | 'files';

@Component({
  selector: 'app-saved',
  imports: [RouterLink, PmIcon, PageFrame],
  templateUrl: './saved.html',
  styleUrl: './saved.scss',
})
export class Saved {
  readonly collections = COLLECTIONS;
  readonly tabs: { id: Tab; label: string }[] = [
    { id: 'all', label: 'All' },
    { id: 'messages', label: 'Messages' },
    { id: 'files', label: 'Files' },
  ];

  collection = signal<string>(COLLECTIONS[0].name);
  tab = signal<Tab>('all');
  query = signal('');
  picked = signal<number | null>(null);

  visible = computed(() => {
    const q = this.query().trim().toLowerCase();
    const col = this.collection();
    return SAVED.filter((s) => {
      if (col !== COLLECTIONS[0].name && s.collection !== col) return false;
      if (this.tab() === 'files' && !s.attachment) return false;
      if (this.tab() === 'messages' && s.attachment) return false;
      return !q || `${s.body} ${s.author}`.toLowerCase().includes(q);
    });
  });

  days = computed(() => [
    { label: 'Today', items: this.visible().filter((s) => s.day === 'today') },
    { label: 'Yesterday', items: this.visible().filter((s) => s.day === 'yesterday') },
  ]);

  /** The item shown in the right panel: the one clicked, else the first with a file. */
  focus = computed<SavedMessage | undefined>(() => {
    const id = this.picked();
    return (
      SAVED.find((s) => s.id === id) ?? this.visible().find((s) => s.attachment) ?? this.visible()[0]
    );
  });

  pick(s: SavedMessage): void {
    this.picked.set(s.id);
  }
}
