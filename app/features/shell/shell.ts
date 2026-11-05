import { Component, computed, inject } from '@angular/core';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { AuthService } from '../../core/services/auth';
import { PmIcon, PmIconName } from '../../shared/icon/icon';

interface RailItem {
  path: string;
  label: string;
  icon: PmIconName;
}

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, PmIcon],
  templateUrl: './shell.html',
  styleUrl: './shell.scss',
})
export class Shell {
  private auth = inject(AuthService);

  readonly items: RailItem[] = [
    { path: '/messages', label: 'Messages', icon: 'chat' },
    { path: '/groups', label: 'Groups', icon: 'groups' },
    { path: '/saved', label: 'Saved', icon: 'saved' },
    { path: '/contacts', label: 'Contacts', icon: 'contacts' },
    { path: '/search', label: 'Search', icon: 'search' },
  ];

  initial = computed(() => (this.auth.currentUser()?.name ?? 'R').charAt(0).toUpperCase());
}
