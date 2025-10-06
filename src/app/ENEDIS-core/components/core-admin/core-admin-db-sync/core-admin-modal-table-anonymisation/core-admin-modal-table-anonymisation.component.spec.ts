import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminModalTableAnonymisationComponent } from './core-admin-modal-table-anonymisation.component';

describe('CoreAdminModalTableAnonymisationComponent', () => {
  let component: CoreAdminModalTableAnonymisationComponent;
  let fixture: ComponentFixture<CoreAdminModalTableAnonymisationComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [CoreAdminModalTableAnonymisationComponent]
    })
    .compileComponents();
    
    fixture = TestBed.createComponent(CoreAdminModalTableAnonymisationComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
