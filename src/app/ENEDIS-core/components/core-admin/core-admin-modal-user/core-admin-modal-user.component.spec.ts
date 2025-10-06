import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminModalUserComponent } from './core-admin-modal-user.component';

describe('ModalUserComponent', () => {
  let component: CoreAdminModalUserComponent;
  let fixture: ComponentFixture<CoreAdminModalUserComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreAdminModalUserComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminModalUserComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
