import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { SecondaryNavbarsComponent } from './secondary-navbars.component';

describe('SecondaryNavabrsComponent', () => {
  let component: SecondaryNavbarsComponent;
  let fixture: ComponentFixture<SecondaryNavbarsComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ SecondaryNavbarsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SecondaryNavbarsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
