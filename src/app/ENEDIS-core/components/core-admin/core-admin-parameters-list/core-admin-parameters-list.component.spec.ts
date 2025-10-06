import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminParametersListComponent } from './core-admin-parameters-list.component';

describe('ParametersListComponent', () => {
  let component: CoreAdminParametersListComponent;
  let fixture: ComponentFixture<CoreAdminParametersListComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [CoreAdminParametersListComponent]
    })
      .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminParametersListComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
