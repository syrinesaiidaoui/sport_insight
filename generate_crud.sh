#!/bin/bash
cd "c:\Users\bouch\OneDrive\Desktop\sport_insight-main"

# Remove manual controller
rm -f src/Controller/BackOffice/EquipementController.php

# Generate CRUD for Product
php bin/console make:crud Product --no-template

# Generate CRUD for Order
php bin/console make:crud Order --no-template
