import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminRolesListComponent } from './core-admin-roles-list.component';

describe('RolesListComponent', () => {
  let component: CoreAdminRolesListComponent;
  let fixture: ComponentFixture<CoreAdminRolesListComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ CoreAdminRolesListComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminRolesListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
