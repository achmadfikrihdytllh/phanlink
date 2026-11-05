import { Component, computed, signal } from '@angular/core';
import { RouterLink } from '@angular/router';

import { CONFIDANTS, GROUPS, GROUP_FILES, Group } from '../../core/mock/phantom-data';
import { PmIcon } from '../../shared/icon/icon';
import { PageFrame } from '../../shared/page-frame/page-frame';

@Component({
  selector: 'app-groups',
  imports: [RouterLink, PmIcon, PageFrame],
  templateUrl: './groups.html',
  styleUrl: './groups.scss',
})
export class Groups {
  groups = GROUPS;
  members = CONFIDANTS;
  files = GROUP_FILES;

  selectedId = signal(GROUPS[0].id);
  query = signal('');

  selected = computed<Group>(() => this.groups.find((g) => g.id === this.selectedId()) ?? this.groups[0]);

  visible = computed(() => {
    const q = this.query().trim().toLowerCase();
    return q ? this.groups.filter((g) => g.name.toLowerCase().includes(q)) : this.groups;
  });

  notifications = signal(true);
  pinToMessages = signal(true);

  select(g: Group): void {
    this.selectedId.set(g.id);
  }
}
