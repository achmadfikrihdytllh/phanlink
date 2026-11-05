import { User } from './user';

export interface ConversationParticipant
  extends Pick<User, 'id' | 'name' | 'username' | 'avatar' | 'status'> {
  role: 'member' | 'admin';
}

export interface LatestMessagePreview {
  id: number;
  body: string | null;
  type: string;
  sender_id: number;
  created_at: string;
}

export interface Conversation {
  id: number;
  type: 'private' | 'group';
  name: string | null;
  avatar: string | null;
  created_by: number;
  participants: ConversationParticipant[];
  latest_message: LatestMessagePreview | null;
  created_at: string;
  updated_at: string;
}