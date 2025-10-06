import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminImportsModalImportFileComponent } from './core-admin-imports-modal-import-file.component';

describe('CoreAdminImportsModalImportFileComponent', () => {
  let component: CoreAdminImportsModalImportFileComponent;
  let fixture: ComponentFixture<CoreAdminImportsModalImportFileComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreAdminImportsModalImportFileComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreAdminImportsModalImportFileComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
