Marello Bridge Magento 2 Extension
=============

Marello Bridge extension creates the ability to push entities from Magento to Marello via the Marello API.
The extension will create a queued record for sending order (updates) to Marello. This includes creating a customer if
the customer is not in the Marello application.

Developers are able to create their own processors to process different entities through some configuration and
implementation of certain classes. More on extending and configuring own processors in the HOW-TO-USE.md.

**Features include:**
- Send orders
- Send order updates
- Queued processing of entities
- Import products from Marello
- Update orders in Magento (including creation of shipments)
- Configure Marello connection settings through backend
- Ping Marello application (for availability of application)

