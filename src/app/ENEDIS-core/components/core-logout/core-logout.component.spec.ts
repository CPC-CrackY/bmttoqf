import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreLogoutComponent } from './core-logout.component';

describe('CoreLogoutComponent', () => {
  let component: CoreLogoutComponent;
  let fixture: ComponentFixture<CoreLogoutComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ CoreLogoutComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreLogoutComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
