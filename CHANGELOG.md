# Release Notes for plex_integration

## 1.0.0
- Initial release

## 1.0.1
- [FIX] Changed the vars parsing

## 1.0.2
- [FIX] Added the .env var parsing for Signature
- [FIX] Addeed the order status update by status handle

## 1.0.3
- [FIX] Order status handle error

## 1.0.4
- [FIX] Using the correct SalePrice on the payload creation

## 1.0.5-beta
- [NEW] Added the new type to the job list, update_shippinginfo. 
- [NEW] Added the updateShippingLogic

## 1.0.5-beta2
- [FIX] fixed dyvision by zero due to free order test

## 1.0.6
- [FIX] Added the address id to the middleware payload
- [NEW] Added the fullfilment webhook

## 1.0.7
- [FIX] Address ID

## 1.0.8
- [FIX] Fixed the transaction ID 
- [FIX] Fixed the payment method name

## 1.0.9
- [NEW] Added the shipping method to the payload
- [NEW] Added the discounts array

## 1.1.0
- [FIX] Discount calculation error

## 1.1.1
- [FIX] Fulfillment fix for multiple tracking numbers

## 1.2.0
- [NEW] Added the Fedex Integration
- [NEW] Added the migration to create the ETA delivery from the Middleware

