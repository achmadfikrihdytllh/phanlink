import { Component, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';

import { AuthService } from '../../core/services/auth';
import { PmIcon, PmIconName } from '../../shared/icon/icon';
import { PageFrame } from '../../shared/page-frame/page-frame';

type Theme = 'phantom' | 'midnight' | 'paper';

interface Prefs {
  theme: Theme;
  density: 'Comfortable' | 'Compact';
  desktopNotifications: boolean;
  messagePreviews: boolean;
  whoCanMessage: 'Everyone' | 'Contacts only';
  readReceipts: boolean;
  showOnline: boolean;
}

const STORAGE_KEY = 'phanlink.prefs';

const DEFAULTS: Prefs = {
  theme: 'phantom',
  density: 'Comfortable',
  desktopNotifications: true,
  messagePreviews: false,
  whoCanMessage: 'Contacts only',
  readReceipts: true,
  showOnline: true,
};

@Component({
  selector: 'app-settings',
  imports: [FormsModule, PmIcon, PageFrame],
  templateUrl: './settings.html',
  styleUrl: './settings.scss',
})
export class Settings {
  private auth = inject(AuthService);

  readonly nav: { label: string; icon: PmIconName }[] = [
    { label: 'Your account', icon: 'user' },
    { label: 'Appearance', icon: 'palette' },
    { label: 'Notifications', icon: 'bell' },
    { label: 'Privacy & security', icon: 'lock' },
    { label: 'Storage & data', icon: 'storage' },
    { label: 'Keyboard shortcuts', icon: 'keyboard' },
  ];

  readonly themes: { id: Theme; label: string; swatch: string }[] = [
    { id: 'phantom', label: 'Phantom', swatch: '#e4182c' },
    { id: 'midnight', label: 'Midnight', swatch: '#0d0d0f' },
    { id: 'paper', label: 'Paper', swatch: '#f4f0e9' },
  ];

  prefs = signal<Prefs>(this.load());
  saved = signal(true);

  user = this.auth.currentUser;
  displayName = computed(() => this.user()?.name ?? 'Ren Amamiya');
  username = computed(() => this.user()?.username ?? 'joker');

  set<K extends keyof Prefs>(key: K, value: Prefs[K]): void {
    this.prefs.update((p) => ({ ...p, [key]: value }));
    this.saved.set(false);
  }

  toggle(key: 'desktopNotifications' | 'messagePreviews' | 'readReceipts' | 'showOnline'): void {
    this.set(key, !this.prefs()[key]);
  }

  save(): void {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(this.prefs()));
    } catch {
      // Storage can be blocked (private mode); keep the in-memory values.
    }
    this.saved.set(true);
  }

  reset(): void {
    this.prefs.set({ ...DEFAULTS });
    this.saved.set(false);
  }

  signOut(): void {
    this.auth.logout().subscribe();
  }

  private load(): Prefs {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      return raw ? { ...DEFAULTS, ...JSON.parse(raw) } : { ...DEFAULTS };
    } catch {
      return { ...DEFAULTS };
    }
  }
}
