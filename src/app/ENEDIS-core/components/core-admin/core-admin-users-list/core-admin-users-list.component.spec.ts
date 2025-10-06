import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminUsersListComponent } from './core-admin-users-list.component';

describe('CoreAdminUsersListComponent', () => {
  let component: CoreAdminUsersListComponent;
  let fixture: ComponentFixture<CoreAdminUsersListComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ CoreAdminUsersListComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminUsersListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
