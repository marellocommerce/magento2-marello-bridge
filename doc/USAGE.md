Extending the Marello Bridge Extension
=================


## Use different Client for communication
In order to let developers choose what they would like to use as a client for communication between Marello and Magento, they can implement their own client by:
* Set a different preference for the `Marello\Bridge\Api\TransportClientInterface` in the `di.xml` and implement the `TransportClientInterface`

```xml
    <preference for="Marello\Bridge\Api\TransportClientInterface" type="Vendor\Module\Somewhere\MyNewCoolClient" />
```

Remember when using a different client, you will still need to use a form of generating the correct headers for the authentication. You could use the `\Marello\Api\Authentication.php` from the Marello Bridge API extension to generate them for you.

## Create different Connectors, Converters and Processors
We have set up the way we select Connectors, Converters and Processors in such a way you can easily add a new one, if needed. In order to add a new one, you'll need to register the connector, converter or processor in the appropriate registry.
 
### Connectors
You can either specify a new connector for exporting data to Marello, or for importing data into Magento. For exporting data to Marello you can use:

```xml
    <type name="Marello\Bridge\Model\Connector\ConnectorRegistry">
        <arguments>
            <argument name="connectors" xsi:type="array">
                <item name="export" xsi:type="array">
                    <item name="mynewexportconnector" xsi:type="object">Vendor\Module\Model\Connector\MyNewCoolExportConnector</item>
                </item>
            </argument>
        </arguments>
    </type>
```
For importing data into Magento you can use:
```xml
    <type name="Marello\Bridge\Model\Connector\ConnectorRegistry">
        <arguments>
            <argument name="connectors" xsi:type="array">
                <item name="import" xsi:type="array">
                    <item name="mynewimportconnector" xsi:type="object">Vendor\Module\Model\Connector\MyNewCoolImportConnector</item>
                </item>
            </argument>
        </arguments>
    </type>
```

The item name in the defined xml above will be the alias for the getting your newly created connector. The newly created connector should implement the `\Marello\Bridge\Api\Data\ConnectorInterface`. 


### Converters
As with the connectors the Converters have a registry of their own. You can register a new converter the same way as with the Connectors:
```xml
    <type name="Marello\Bridge\Model\Converter\DataConverterRegistry">
        <arguments>
            <argument name="converters" xsi:type="array">
                <item name="mynewconverter" xsi:type="object">Vendor\Module\Model\Converter\MyNewConverter</item>
            </argument>
        </arguments>
    </type>
```
The item name in the defined xml above will be the alias for the getting your newly created converter. The newly created connector should implement the `\Marello\Bridge\Api\Data\DataConverterInterface`.

### Processors
Processors can be registered within the Processor Registry.
```xml
    <type name="Marello\Bridge\Model\Processor\ProcessorRegistry">
        <arguments>
            <argument name="processors" xsi:type="array">
                <item name="mynewprocessor" xsi:type="object">Vendor\Module\Model\Processor\MyNewProcessor</item>
            </argument>
        </arguments>
    </type>
```

The item name in the defined xml above will be the alias for the getting your newly created processor. The newly created connector should implement the `\Marello\Bridge\Api\MarelloProcessorInterface`.
You could extend the `\Marello\Bridge\Model\Processor\AbstractProcessor` which already has the interface implemented.

## Commands
The extension has several commands which serve a specific purpose. The commands vary in their uses

`marello:app:status`, will try and ping the Marello instance configured in the Admin backend. It will try to fetch a list of users which will either verify if the instance is up and running and or if your authentication is correct.
`marello:cron:import-products`, will get all the products from the Marello instance and will process them accordingly. This command also runs in a cron once every night at 1.15. 
`marello:cron:update-orders`, will fetch the latest order statuses for the orders which are still on status 'processing' and has marello_data available.
`marello:cron:process-queue`, will process all records which are currently in the queue. They can be order creation or order updates (invoicing / cancellation) in Marello. The queue is running in the cron every 5 minutes.

## Events
Currently one event is being monitored for the creation of a certain EntityQueue record, which is `sales_order_save_after`. Based on the order data in the event the different observers will create a EntityQueue record for a specific type of event. These will translate in roughly entity order_create, order_invoice and order_cancel.
