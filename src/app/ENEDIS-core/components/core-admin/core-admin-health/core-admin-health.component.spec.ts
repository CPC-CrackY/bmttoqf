import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreAdminHealthComponent } from './core-admin-health.component';

describe('CoreAdminHealthComponent', () => {
  let component: CoreAdminHealthComponent;
  let fixture: ComponentFixture<CoreAdminHealthComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [CoreAdminHealthComponent]
    })
    .compileComponents();
    
    fixture = TestBed.createComponent(CoreAdminHealthComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
