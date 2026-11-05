import { User } from './user';

export interface MessageAttachment {
  id: number;
  file_path: string;
  file_type: string;
  file_size: number;
  thumbnail_path: string | null;
}

export interface ReplyToMessage {
  id: number;
  body: string | null;
  sender_id: number;
}

export interface Message {
  id: number;
  conversation_id: number;
  sender: Pick<User, 'id' | 'name' | 'username' | 'avatar'>;
  type: 'text' | 'image' | 'file' | 'system';
  body: string | null;
  reply_to: ReplyToMessage | null;
  attachments: MessageAttachment[];
  edited_at: string | null;
  created_at: string;
}

export interface MessagePaginatedResponse {
  messages: Message[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}