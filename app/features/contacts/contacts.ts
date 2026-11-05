import { Component, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { CONFIDANTS, GROUPS, GROUP_FILES, Confidant } from '../../core/mock/phantom-data';
import { PmIcon } from '../../shared/icon/icon';
import { PageFrame } from '../../shared/page-frame/page-frame';

type Filter = 'all' | 'online';

@Component({
  selector: 'app-contacts',
  imports: [RouterLink, PmIcon, PageFrame],
  templateUrl: './contacts.html',
  styleUrl: './contacts.scss',
})
export class Contacts {
  readonly contacts = CONFIDANTS;
  readonly groups = GROUPS.slice(0, 3);
  readonly files = GROUP_FILES;
  readonly tabs = ['Profile', 'Media', 'Links'];

  filter = signal<Filter>('all');
  query = signal('');
  selectedId = signal(CONFIDANTS[0].id);
  tab = signal('Profile');
  muted = signal(false);
  note = signal('Ask about track practice. And the ramen place near Shibuya.');

  onlineCount = this.contacts.filter((c) => c.status === 'online').length;

  visible = computed(() => {
    const q = this.query().trim().toLowerCase();
    return this.contacts.filter(
      (c) =>
        (this.filter() === 'all' || c.status === 'online') &&
        (!q || `${c.name} ${c.codename}`.toLowerCase().includes(q)),
    );
  });

  selected = computed<Confidant>(
    () => this.contacts.find((c) => c.id === this.selectedId()) ?? this.contacts[0],
  );
}
