import { Component, computed, inject, input } from '@angular/core';

import { AuthService } from '../../core/services/auth';
import { PmIcon } from '../icon/icon';

/**
 * Three-column frame shared by every page: list | main | aside.
 * Pages project content with `slot="list"`, `slot="main"` and `slot="aside"`.
 */
@Component({
  selector: 'pm-page-frame',
  imports: [PmIcon],
  template: `
    <section class="list" [class.list--dark]="listTone() === 'dark'">
      <ng-content select="[slot=list]" />

      <footer class="me">
        <span class="me__avatar">{{ initial() }}</span>
        <span class="me__text">
          <strong>{{ displayName() }}</strong>
          <small>Online · Laying low</small>
        </span>
        <pm-icon name="chevron-down" [size]="12" />
      </footer>
    </section>

    <section class="main">
      <ng-content select="[slot=main]" />
    </section>

    <aside class="aside">
      <div class="aside__body pm-scroll">
        <ng-content select="[slot=aside]" />
      </div>

      <footer class="brand">
        <span class="brand__title pm-title">Take your heart.</span>
        <span class="brand__sub">Stay connected. Stay rebellious.</span>
      </footer>
    </aside>
  `,
  styleUrl: './page-frame.scss',
})
export class PageFrame {
  private auth = inject(AuthService);

  listTone = input<'paper' | 'dark'>('paper');

  displayName = computed(() => this.auth.currentUser()?.name ?? 'Ren Amamiya');
  initial = computed(() => this.displayName().charAt(0).toUpperCase());
}
