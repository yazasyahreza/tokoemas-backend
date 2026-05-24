# Toko Emas REST API Documentation

Backend REST API for Gold Store built with CodeIgniter 3.

## Setup Instructions

1.  **Dependencies**: Run `composer update` to install JWT and Dotenv libraries.
2.  **Environment**: 
    - Copy `.env.example` to `.env`.
    - Configure your database credentials in `.env`.
    - Generate/set a `JWT_SECRET` in `.env`.
3.  **Database**:
    - Import the `database.sql` file to your MySQL/MariaDB server.
4.  **CORS**:
    - CORS is already enabled in `API_Controller.php`.

## Authentication

All private endpoints require a Bearer Token in the header:
`Authorization: Bearer <your_jwt_token>`

## Main Endpoints

### Auth
- `POST /api/v1/auth/register`: Register a new user.
- `POST /api/v1/auth/login`: Login and get JWT token.
- `GET /api/v1/auth/profile`: Get logged-in user profile (Private).

### Products & Categories
- `GET /api/v1/categories`: Get all categories.
- `GET /api/v1/products`: List products with filters (search, price, category).
- `GET /api/v1/products/{slug}`: Get product details including variants and reviews.

### Shopping Cart (Private)
- `GET /api/v1/cart`: Get user's cart items.
- `POST /api/v1/cart/add`: Add product/variant to cart.
- `POST /api/v1/cart/update/{item_id}`: Update item quantity.
- `DELETE /api/v1/cart/delete/{item_id}`: Remove item.

### Orders (Private)
- `POST /api/v1/orders/checkout`: Create a new order from cart.
- `GET /api/v1/orders`: List user's orders.
- `GET /api/v1/orders/detail/{order_id}`: Get order details.
- `POST /api/v1/orders/upload-proof/{order_id}`: Upload payment receipt.

### Shipping Addresses (Private)
- `GET /api/v1/shipping-addresses`: List addresses.
- `POST /api/v1/shipping-addresses/add`: Create new address.
- `POST /api/v1/shipping-addresses/set-default/{id}`: Set default address.

## Response Format

```json
{
  "status": true,
  "message": "Success message",
  "data": {}
}
```
