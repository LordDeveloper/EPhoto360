# EPhoto360
Create text effects online , Effects online for free, photo frames, make face photo montages, custom greeting cards, add vintage filters, turn photos into sketches and drawings

# Requirements
- PHP > 8
- Composer
- Git (For installing as a Github repository)
- Phar (For installing as Phar project)

# Installing methods

**Installing as Github repository**
- ```git clone https://github.com/LordDeveloper/EPhoto360.git && cd EPhoto360 && composer install -o -no-dev```

**Installing with Composer**
- ```composer require jupiterapi/ephoto360:1.0.x-dev```

**Installing with Phar**
- ```copy('https://phar.lorddeveloper.ir/ephoto360.phar', 'ephoto360.phar');```

  - **Usage:**
  
  ```<?php
  use JupiterAPI\EPhoto360;
  require_once 'phar://ephoto360.phar/vendor/autoload.php';
  
  
  var_dump(EPhoto360::getEffect(22));

