import { Component, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { SEARCH_HITS, SearchHit } from '../../core/mock/phantom-data';
import { PmIcon } from '../../shared/icon/icon';
import { PageFrame } from '../../shared/page-frame/page-frame';

interface Segment {
  text: string;
  hit: boolean;
}

@Component({
  selector: 'app-search',
  imports: [RouterLink, PmIcon, PageFrame],
  templateUrl: './search.html',
  styleUrl: './search.scss',
})
export class Search {
  query = signal('Leblanc');
  showMessages = signal(true);
  showFiles = signal(true);
  recent = signal(['game plan', 'ramen']);
  pickedId = signal<number | null>(null);

  results = computed(() => {
    const q = this.query().trim().toLowerCase();
    if (!q) return [];
    return SEARCH_HITS.filter(
      (h) =>
        h.body.toLowerCase().includes(q) &&
        (h.kind === 'message' ? this.showMessages() : this.showFiles()),
    );
  });

  messages = computed(() => this.results().filter((h) => h.kind === 'message'));
  files = computed(() => this.results().filter((h) => h.kind === 'file'));

  focus = computed<SearchHit | undefined>(
    () => this.results().find((h) => h.id === this.pickedId()) ?? this.messages()[0],
  );

  /** Split text around each match so the template can highlight it without innerHTML. */
  segments(text: string): Segment[] {
    const q = this.query().trim();
    if (!q) return [{ text, hit: false }];

    const lower = text.toLowerCase();
    const needle = q.toLowerCase();
    const out: Segment[] = [];
    let from = 0;

    for (let at = lower.indexOf(needle); at !== -1; at = lower.indexOf(needle, from)) {
      if (at > from) out.push({ text: text.slice(from, at), hit: false });
      out.push({ text: text.slice(at, at + needle.length), hit: true });
      from = at + needle.length;
    }
    if (from < text.length) out.push({ text: text.slice(from), hit: false });
    return out;
  }

  useRecent(term: string): void {
    this.query.set(term);
  }

  resetFilters(): void {
    this.showMessages.set(true);
    this.showFiles.set(true);
  }
}
