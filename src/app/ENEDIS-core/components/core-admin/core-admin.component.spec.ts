import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminComponent } from './core-admin.component';

describe('CoreAdminComponent', () => {
  let component: CoreAdminComponent;
  let fixture: ComponentFixture<CoreAdminComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ CoreAdminComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
