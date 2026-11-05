import { TestBed } from '@angular/core/testing';

import { Conversation } from './conversation';

describe('Conversation', () => {
  let service: Conversation;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(Conversation);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
