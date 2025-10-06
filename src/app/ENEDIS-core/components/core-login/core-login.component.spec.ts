import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreLoginComponent } from './core-login.component';

describe('CoreLoginComponent', () => {
  let component: CoreLoginComponent;
  let fixture: ComponentFixture<CoreLoginComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ CoreLoginComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CoreLoginComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
