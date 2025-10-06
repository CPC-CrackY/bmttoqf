import { waitForAsync, ComponentFixture, TestBed } from '@angular/core/testing';

import { SimpleFilterComponent } from './simple-filter.component';

describe('SimpleFilterComponent', () => {
  let component: SimpleFilterComponent;
  let fixture: ComponentFixture<SimpleFilterComponent>;

  beforeEach(waitForAsync(() => {
    TestBed.configureTestingModule({
      declarations: [ SimpleFilterComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SimpleFilterComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
