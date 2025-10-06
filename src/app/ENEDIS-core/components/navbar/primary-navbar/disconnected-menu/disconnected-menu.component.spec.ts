import { ComponentFixture, TestBed } from '@angular/core/testing';

import { DisconnectedMenuComponent } from './disconnected-menu.component';

describe('DisconnectedMenuComponent', () => {
  let component: DisconnectedMenuComponent;
  let fixture: ComponentFixture<DisconnectedMenuComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ DisconnectedMenuComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(DisconnectedMenuComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
