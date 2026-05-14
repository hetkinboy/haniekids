import { createReducer, on } from '@ngrx/store';
import { Product } from '../core/api/api.models';
import { productSelected, productsLoaded } from './product.actions';

export interface ProductState {
  items: Product[];
  total: number;
  selected: Product | null;
}

export const initialProductState: ProductState = {
  items: [],
  total: 0,
  selected: null,
};

export const productReducer = createReducer(
  initialProductState,
  on(productsLoaded, (state, action) => ({ ...state, items: action.products, total: action.total })),
  on(productSelected, (state, action) => ({ ...state, selected: action.product })),
);

