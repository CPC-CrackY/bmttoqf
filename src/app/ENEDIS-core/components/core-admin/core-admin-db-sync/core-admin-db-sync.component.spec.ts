import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminDbSyncComponent } from './core-admin-db-sync.component';

describe('CoreAdminDbSyncComponent', () => {
  let component: CoreAdminDbSyncComponent;
  let fixture: ComponentFixture<CoreAdminDbSyncComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreAdminDbSyncComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CoreAdminDbSyncComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
