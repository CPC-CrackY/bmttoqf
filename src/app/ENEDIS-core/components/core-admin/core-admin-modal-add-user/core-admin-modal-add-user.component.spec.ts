import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminModalAddUserComponent } from './core-admin-modal-add-user.component';

describe('CoreAdminModalAddUserComponent', () => {
  let component: CoreAdminModalAddUserComponent;
  let fixture: ComponentFixture<CoreAdminModalAddUserComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreAdminModalAddUserComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminModalAddUserComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
