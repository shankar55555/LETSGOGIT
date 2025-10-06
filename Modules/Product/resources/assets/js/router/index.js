import CreateProduct from '../add/AddDrawer.vue'
import ProductDetail from '../details/[id].vue'
import EditProduct from '../edit/EditDrawer.vue'
import ProductList from '../list/index.vue'
import StockCreate from '../stock/add/AddDrawer.vue'
import Stock from '../stock/index.vue'
import Vendor from '../vendor/index.vue'
import VendorDetail from '../vendor/view/[id].vue'

export default [

  // Product Section Routes
  {
    path: '/product',
    name: 'product-list',
    component: ProductList,
    meta: { title: 'Product' },
  },
  {
    path: '/product/create',
    name: 'product-create',
    component: CreateProduct,
    meta: { title: 'Create Product' },
  },
  {
    path: '/product/edit/:id',
    name: 'product-edit',
    component: EditProduct,
    meta: { title: 'Edit Product' },
  },
  {
    path: '/product/details/:id',
    name: 'product-details-id',
    component: ProductDetail,
    meta: { title: 'Product' },
  },

  // Vendor Section Routes
  {
    path: '/vendor',
    name: 'vendor-list',
    component: Vendor,
    meta: { title: 'Product' },

  },
  {
    path: '/vendor/:id',
    name: 'vendor-view-id',
    component: VendorDetail,
    meta: { title: 'Vendor Details' },
  },


  // Stock Section Routes
  {
    path: '/stock',
    name: 'stock',
    component: Stock,
    meta: { title: 'Product' },
  },
  {
    path: '/stock/create',
    name: 'stock-create',
    component: StockCreate,
    meta: { title: 'Create Product' },

  },
]

