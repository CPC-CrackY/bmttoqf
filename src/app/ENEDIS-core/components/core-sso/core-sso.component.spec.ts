import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreSSOComponent } from './core-sso.component';

describe('CoreSSOComponent', () => {
  let component: CoreSSOComponent;
  let fixture: ComponentFixture<CoreSSOComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreSSOComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreSSOComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
