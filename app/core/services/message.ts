import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Message, MessagePaginatedResponse } from '../models/message';

@Injectable({
  providedIn: 'root',
})
export class MessageService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  getMessages(conversationId: number, page: number = 1): Observable<MessagePaginatedResponse> {
    return this.http.get<MessagePaginatedResponse>(
      `${this.apiUrl}/conversations/${conversationId}/messages?page=${page}`
    );
  }

  sendText(conversationId: number, body: string, replyToId?: number): Observable<{ data: Message }> {
    return this.http.post<{ data: Message }>(
      `${this.apiUrl}/conversations/${conversationId}/messages`,
      {
        type: 'text',
        body,
        reply_to_id: replyToId ?? null,
      }
    );
  }

  markAsRead(conversationId: number): Observable<{ message: string }> {
    return this.http.post<{ message: string }>(
      `${this.apiUrl}/conversations/${conversationId}/read`,
      {}
    );
  }
}