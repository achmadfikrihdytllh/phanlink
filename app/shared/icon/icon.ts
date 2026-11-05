import { Component, computed, input } from '@angular/core';

export type PmIconName =
  | 'chat'
  | 'groups'
  | 'saved'
  | 'archive'
  | 'contacts'
  | 'search'
  | 'settings'
  | 'compose'
  | 'plus'
  | 'phone'
  | 'more'
  | 'send'
  | 'smile'
  | 'file'
  | 'download'
  | 'arrow'
  | 'star'
  | 'pin'
  | 'link'
  | 'image'
  | 'close'
  | 'chevron-down'
  | 'logout'
  | 'bell'
  | 'lock'
  | 'palette'
  | 'keyboard'
  | 'storage'
  | 'user'
  | 'edit'
  | 'device';

/** Stroke paths on a 24x24 grid, kept in one place so pages stay readable. */
const PATHS: Record<PmIconName, string> = {
  chat: 'M21 12a8 8 0 0 1-11.6 7.1L4 20l1-4.6A8 8 0 1 1 21 12Z',
  groups:
    'M16 20v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 18.5V20M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM20 20v-1.5a3.5 3.5 0 0 0-2.5-3.35M15.5 4.15a3.5 3.5 0 0 1 0 6.7',
  saved: 'M6 4h12v17l-6-4-6 4V4Z',
  archive: 'M4 7h16v3H4V7Zm1 3h14v10H5V10Zm4 4h6',
  contacts: 'M5 4h14v16H5V4Zm7 7a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Zm-4.5 7a4.5 4.5 0 0 1 9 0',
  search: 'm20 20-4.2-4.2M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z',
  settings:
    'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.5-2-3.4-2.3.9a7.6 7.6 0 0 0-2-1.2L14.6 3h-4l-.4 2.6a7.6 7.6 0 0 0-2 1.2l-2.3-.9-2 3.4 2 1.5a7.4 7.4 0 0 0 0 2.4l-2 1.5 2 3.4 2.3-.9a7.6 7.6 0 0 0 2 1.2l.4 2.6h4l.4-2.6a7.6 7.6 0 0 0 2-1.2l2.3.9 2-3.4-2-1.5c.1-.4.1-.8.1-1.2Z',
  compose: 'M4 20h4L19 9l-4-4L4 16v4Zm9-13 4 4',
  plus: 'M12 5v14M5 12h14',
  phone:
    'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1A16 16 0 0 1 4 5a1 1 0 0 1 1-1Z',
  more: 'M5 12h.01M12 12h.01M19 12h.01',
  send: 'M5 12h14m-6-6 6 6-6 6',
  smile: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm-3.5-7a4 4 0 0 0 7 0M9 9.5h.01M15 9.5h.01',
  file: 'M7 3h7l4 4v14H7V3Zm7 0v4h4',
  download: 'M12 4v11m-4-4 4 4 4-4M5 20h14',
  arrow: 'M7 17 17 7M9 7h8v8',
  star: 'm12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z',
  pin: 'M9 4h6l-1 6 3 3H7l3-3-1-6Zm3 9v7',
  link: 'M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1M14 10a4 4 0 0 0-5.7 0l-3 3A4 4 0 0 0 11 18.7l1-1',
  image: 'M4 5h16v14H4V5Zm0 11 5-5 4 4 3-3 4 4M15 9.5h.01',
  close: 'm6 6 12 12M18 6 6 18',
  'chevron-down': 'm6 9 6 6 6-6',
  logout: 'M10 4H5v16h5M15 8l4 4-4 4M19 12H9',
  bell: 'M6 16V11a6 6 0 1 1 12 0v5l2 2H4l2-2Zm4 4a2 2 0 0 0 4 0',
  lock: 'M6 11h12v9H6v-9Zm3 0V8a3 3 0 0 1 6 0v3',
  palette:
    'M12 3a9 9 0 1 0 0 18c1.2 0 1.7-.9 1.4-1.8-.4-1.1.2-2.2 1.4-2.2H17a4 4 0 0 0 4-4c0-5-4-10-9-10ZM7.5 11h.01M10 7.5h.01M14.5 7.5h.01',
  keyboard: 'M3 6h18v12H3V6Zm4 8h10M7 10h.01M11 10h.01M15 10h.01M19 10h.01',
  storage: 'M4 6c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3Zm0 0v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6',
  user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
  edit: 'M4 20h4L19 9l-4-4L4 16v4Z',
  device: 'M4 5h16v11H4V5Zm4 15h8',
};

@Component({
  selector: 'pm-icon',
  template: `
    <svg
      [attr.width]="size()"
      [attr.height]="size()"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      stroke-width="2"
      stroke-linecap="square"
      stroke-linejoin="miter"
      aria-hidden="true"
    >
      <path [attr.d]="path()" />
    </svg>
  `,
  styles: `
    :host {
      display: inline-flex;
      line-height: 0;
    }
  `,
})
export class PmIcon {
  name = input.required<PmIconName>();
  size = input(14);
  path = computed(() => PATHS[this.name()]);
}
