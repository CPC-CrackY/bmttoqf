import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminImportsComponent } from './core-admin-imports.component';

describe('CoreAdminImportsComponent', () => {
  let component: CoreAdminImportsComponent;
  let fixture: ComponentFixture<CoreAdminImportsComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreAdminImportsComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminImportsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
