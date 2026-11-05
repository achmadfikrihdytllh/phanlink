/**
 * Local sample data for the pages that have no backend endpoint yet
 * (Groups, Saved, Contacts, Search). Swap each export for a service call
 * when the matching API exists; the page templates only depend on these shapes.
 */

export interface Confidant {
  id: number;
  name: string;
  codename: string;
  handle: string;
  school: string;
  status: 'online' | 'offline';
  note: string;
  lastSeen: string;
}

export interface SharedFile {
  name: string;
  meta: string;
  kind: 'file' | 'image' | 'calendar' | 'map';
}

export interface Group {
  id: number;
  name: string;
  members: number;
  online: number;
  blurb: string;
}

export interface SavedMessage {
  id: number;
  author: string;
  group: string;
  time: string;
  body: string;
  collection: string;
  day: 'today' | 'yesterday';
  attachment?: SharedFile;
}

export const CONFIDANTS: Confidant[] = [
  { id: 1, name: 'Ryuji Sakamoto', codename: 'Skull', handle: '@skull', school: 'Shujin Academy', status: 'online', note: 'Online · Ready for ramen', lastSeen: 'Online' },
  { id: 2, name: 'Ann Takamaki', codename: 'Panther', handle: '@panther', school: 'Shujin Academy', status: 'online', note: 'Online · In the hideout', lastSeen: 'Online' },
  { id: 3, name: 'Makoto Niijima', codename: 'Queen', handle: '@queen', school: 'Shujin Academy', status: 'online', note: 'Online · Reviewing notes', lastSeen: 'Online' },
  { id: 4, name: 'Futaba Sakura', codename: 'Oracle', handle: '@oracle', school: 'Home', status: 'offline', note: 'Last seen at 13:22', lastSeen: 'Last seen at 13:22' },
  { id: 5, name: 'Yusuke Kitagawa', codename: 'Fox', handle: '@fox', school: 'Kosei Arts', status: 'offline', note: 'Last seen yesterday', lastSeen: 'Last seen yesterday' },
  { id: 6, name: 'Morgana', codename: 'Mona', handle: '@mona', school: 'Leblanc', status: 'online', note: 'Online · Laying low', lastSeen: 'Online' },
];

export const GROUPS: Group[] = [
  { id: 1, name: 'The Phantom Thieves', members: 7, online: 4, blurb: 'Ryuji: Okay, okay. I’ll be there!' },
  { id: 2, name: 'Leblanc regulars', members: 4, online: 1, blurb: 'Coffee, curry, and a place to lay low.' },
  { id: 3, name: 'Operation: snack run', members: 3, online: 0, blurb: 'Futaba: Operation is a go.' },
  { id: 4, name: 'After-school plans', members: 5, online: 2, blurb: 'Ann: See you after class!' },
];

export const GROUP_FILES: SharedFile[] = [
  { name: 'Tomorrow’s game plan.pdf', meta: 'Makoto · 2.4 MB · Today', kind: 'file' },
  { name: 'Hideout route.png', meta: 'Ryuji · 680 KB · Yesterday', kind: 'image' },
];

export const SAVED: SavedMessage[] = [
  {
    id: 1, author: 'Ann Takamaki', group: 'The Phantom Thieves', time: 'Today, 16:42',
    body: 'So, Leblanc at five? I’m claiming the booth. We need a proper game plan for tomorrow.',
    collection: 'Plans & operations', day: 'today',
  },
  {
    id: 2, author: 'Makoto Niijima', group: 'The Phantom Thieves', time: 'Today, 16:43',
    body: 'I’ll bring my notes. Let’s go over the route together before we make our next move.',
    collection: 'Plans & operations', day: 'today',
    attachment: { name: 'Tomorrow’s game plan.pdf', meta: 'PDF · 2.4 MB · Plans & operations', kind: 'file' },
  },
  {
    id: 3, author: 'Ryuji Sakamoto', group: 'The Phantom Thieves', time: 'Today, 16:32',
    body: 'You up for ramen after this?', collection: 'Places to go', day: 'today',
  },
  {
    id: 4, author: 'Yusuke Kitagawa', group: 'The Phantom Thieves', time: 'Yesterday, 19:20',
    body: 'A most inspiring afternoon. We should visit the gallery again when we have a moment.',
    collection: 'Links & inspiration', day: 'yesterday',
  },
];

export const COLLECTIONS = [
  { name: 'All saved messages', count: 12 },
  { name: 'Plans & operations', count: 4 },
  { name: 'Places to go', count: 3 },
  { name: 'Photos', count: 3 },
  { name: 'Links & inspiration', count: 2 },
];

export interface SearchHit {
  id: number;
  author: string;
  where: string;
  time: string;
  body: string;
  kind: 'message' | 'file';
}

export const SEARCH_HITS: SearchHit[] = [
  { id: 1, author: 'Ann Takamaki', where: 'The Phantom Thieves', time: 'Today, 16:42', body: 'So, Leblanc at five? I’m claiming the booth. We need a proper game plan for tomorrow.', kind: 'message' },
  { id: 2, author: 'Ren Amamiya', where: 'The Phantom Thieves', time: 'Today, 16:40', body: 'Meet at Leblanc. 17:00. Don’t be late.', kind: 'message' },
  { id: 3, author: 'Ryuji Sakamoto', where: 'Direct message', time: 'Today, 16:35', body: 'Alright, Leblanc first. Ramen after. That’s a plan!', kind: 'message' },
  { id: 4, author: 'Makoto Niijima', where: 'The Phantom Thieves', time: 'Yesterday, 18:08', body: 'I’ve checked the route. We can go over it at Leblanc tomorrow.', kind: 'message' },
  { id: 5, author: 'Ann', where: 'The Phantom Thieves · Today, 16:45', body: 'Leblanc meetup.ics', kind: 'file' },
  { id: 6, author: 'Makoto', where: 'Leblanc regulars · Oct 23, 18:06', body: 'Leblanc directions.png', kind: 'file' },
];
