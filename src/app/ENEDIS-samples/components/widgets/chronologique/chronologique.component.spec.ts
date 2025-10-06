import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { ChronologiqueComponent } from './chronologique.component';

describe('ChronologiqueComponent', () => {
  let component: ChronologiqueComponent;
  let fixture: ComponentFixture<ChronologiqueComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ ChronologiqueComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(ChronologiqueComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
