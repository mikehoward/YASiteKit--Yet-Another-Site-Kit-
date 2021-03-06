<?php
echo "skipping currency code initialization\n";
return;


ObjectInfo::do_require_once('CurrencyCode.php');
ObjectInfo::do_require_once('CountryCode.php');

foreach (array('CurrencyCode', 'CountryCode') as $obj_name) {
  $obj = new $obj_name(Globals::$dbaccess);
  if (!Globals::$dbaccess->table_exists($obj->tablename)) {
    $tmp = AClass::get_class_instance($obj_name);
    $tmp->create_table(Globals::$dbaccess);
  }
}

// do your initialization
$currency_code_data = array(
    array('currency_code' => 'AED', 'country_name' =>	'United Arab Emirates, Dirhams'),
    array('currency_code' => 'AFN', 'country_name' =>'Afghanistan, Afghanis'),
    array('currency_code' => 'ALL', 'country_name' =>'Albania, Leke'),
    array('currency_code' => 'AMD', 'country_name' =>'Armenia, Drams'),
    array('currency_code' => 'ANG', 'country_name' =>'Netherlands Antilles, Guilders (also called Florins)'),
    array('currency_code' => 'AOA', 'country_name' =>'Angola, Kwanza'),
    array('currency_code' => 'ARS', 'country_name' =>'Argentina, Pesos'),
    array('currency_code' => 'AUD', 'country_name' =>'Australia, Dollars'),
    array('currency_code' => 'AWG', 'country_name' =>'Aruba, Guilders (also called Florins)'),
    array('currency_code' => 'AZN', 'country_name' =>'Azerbaijan, New Manats'),
    array('currency_code' => 'BAM', 'country_name' =>'Bosnia and Herzegovina, Convertible Marka'),
    array('currency_code' => 'BBD', 'country_name' =>'Barbados, Dollars'),
    array('currency_code' => 'BDT', 'country_name' =>'Bangladesh, Taka'),
    array('currency_code' => 'BGN', 'country_name' =>'Bulgaria, Leva'),
    array('currency_code' => 'BHD', 'country_name' =>'Bahrain, Dinars'),
    array('currency_code' => 'BIF', 'country_name' =>'Burundi, Francs'),
    array('currency_code' => 'BMD', 'country_name' =>'Bermuda, Dollars'),
    array('currency_code' => 'BND', 'country_name' =>'Brunei Darussalam, Dollars'),
    array('currency_code' => 'BOB', 'country_name' =>'Bolivia, Bolivianos'),
    array('currency_code' => 'BRL', 'country_name' =>'Brazil, Brazil Real'),
    array('currency_code' => 'BSD', 'country_name' =>'Bahamas, Dollars'),
    array('currency_code' => 'BTN', 'country_name' =>'Bhutan, Ngultrum'),
    array('currency_code' => 'BWP', 'country_name' =>'Botswana, Pulas'),
    array('currency_code' => 'BYR', 'country_name' =>'Belarus, Rubles'),
    array('currency_code' => 'BZD', 'country_name' =>'Belize, Dollars'),
    array('currency_code' => 'CAD', 'country_name' =>'Canada, Dollars'),
    array('currency_code' => 'CDF', 'country_name' =>'Congo/Kinshasa, Congolese Francs'),
    array('currency_code' => 'CHF', 'country_name' =>'Switzerland, Francs'),
    array('currency_code' => 'CLP', 'country_name' =>'Chile, Pesos'),
    array('currency_code' => 'CNY', 'country_name' =>'China, Yuan Renminbi'),
    array('currency_code' => 'COP', 'country_name' =>'Colombia, Pesos'),
    array('currency_code' => 'CRC', 'country_name' =>'Costa Rica, Colones'),
    array('currency_code' => 'CUP', 'country_name' =>'Cuba, Pesos'),
    array('currency_code' => 'CVE', 'country_name' =>'Cape Verde, Escudos'),
    array('currency_code' => 'CYP', 'country_name' =>'Cyprus, Pounds (expires 2008-Jan-31)'),
    array('currency_code' => 'CZK', 'country_name' =>'Czech Republic, Koruny'),
    array('currency_code' => 'DJF', 'country_name' =>'Djibouti, Francs'),
    array('currency_code' => 'DKK', 'country_name' =>'Denmark, Kroner'),
    array('currency_code' => 'DOP', 'country_name' =>'Dominican Republic, Pesos'),
    array('currency_code' => 'DZD', 'country_name' =>'Algeria, Algeria Dinars'),
    array('currency_code' => 'EEK', 'country_name' =>'Estonia, Krooni'),
    array('currency_code' => 'EGP', 'country_name' =>'Egypt, Pounds'),
    array('currency_code' => 'ERN', 'country_name' =>'Eritrea, Nakfa'),
    array('currency_code' => 'ETB', 'country_name' =>'Ethiopia, Birr'),
    array('currency_code' => 'EUR', 'country_name' =>'Euro Member Countries, Euro'),
    array('currency_code' => 'FJD', 'country_name' =>'Fiji, Dollars'),
    array('currency_code' => 'FKP', 'country_name' =>'Falkland Islands (Malvinas), Pounds'),
    array('currency_code' => 'GBP', 'country_name' =>'United Kingdom, Pounds'),
    array('currency_code' => 'GEL', 'country_name' =>'Georgia, Lari'),
    array('currency_code' => 'GGP', 'country_name' =>'Guernsey, Pounds'),
    array('currency_code' => 'GHS', 'country_name' =>'Ghana, Cedis'),
    array('currency_code' => 'GIP', 'country_name' =>'Gibraltar, Pounds'),
    array('currency_code' => 'GMD', 'country_name' =>'Gambia, Dalasi'),
    array('currency_code' => 'GNF', 'country_name' =>'Guinea, Francs'),
    array('currency_code' => 'GTQ', 'country_name' =>'Guatemala, Quetzales'),
    array('currency_code' => 'GYD', 'country_name' =>'Guyana, Dollars'),
    array('currency_code' => 'HKD', 'country_name' =>'Hong Kong, Dollars'),
    array('currency_code' => 'HNL', 'country_name' =>'Honduras, Lempiras'),
    array('currency_code' => 'HRK', 'country_name' =>'Croatia, Kuna'),
    array('currency_code' => 'HTG', 'country_name' =>'Haiti, Gourdes'),
    array('currency_code' => 'HUF', 'country_name' =>'Hungary, Forint'),
    array('currency_code' => 'IDR', 'country_name' =>'Indonesia, Rupiahs'),
    array('currency_code' => 'ILS', 'country_name' =>'Israel, New Shekels'),
    array('currency_code' => 'IMP', 'country_name' =>'Isle of Man, Pounds'),
    array('currency_code' => 'INR', 'country_name' =>'India, Rupees'),
    array('currency_code' => 'IQD', 'country_name' =>'Iraq, Dinars'),
    array('currency_code' => 'IRR', 'country_name' =>'Iran, Rials'),
    array('currency_code' => 'ISK', 'country_name' =>'Iceland, Kronur'),
    array('currency_code' => 'JEP', 'country_name' =>'Jersey, Pounds'),
    array('currency_code' => 'JMD', 'country_name' =>'Jamaica, Dollars'),
    array('currency_code' => 'JOD', 'country_name' =>'Jordan, Dinars'),
    array('currency_code' => 'JPY', 'country_name' =>'Japan, Yen'),
    array('currency_code' => 'KES', 'country_name' =>'Kenya, Shillings'),
    array('currency_code' => 'KGS', 'country_name' =>'Kyrgyzstan, Soms'),
    array('currency_code' => 'KHR', 'country_name' =>'Cambodia, Riels'),
    array('currency_code' => 'KMF', 'country_name' =>'Comoros, Francs'),
    array('currency_code' => 'KPW', 'country_name' =>'Korea (North), Won'),
    array('currency_code' => 'KRW', 'country_name' =>'Korea (South), Won'),
    array('currency_code' => 'KWD', 'country_name' =>'Kuwait, Dinars'),
    array('currency_code' => 'KYD', 'country_name' =>'Cayman Islands, Dollars'),
    array('currency_code' => 'KZT', 'country_name' =>'Kazakhstan, Tenge'),
    array('currency_code' => 'LAK', 'country_name' =>'Laos, Kips'),
    array('currency_code' => 'LBP', 'country_name' =>'Lebanon, Pounds'),
    array('currency_code' => 'LKR', 'country_name' =>'Sri Lanka, Rupees'),
    array('currency_code' => 'LRD', 'country_name' =>'Liberia, Dollars'),
    array('currency_code' => 'LSL', 'country_name' =>'Lesotho, Maloti'),
    array('currency_code' => 'LTL', 'country_name' =>'Lithuania, Litai'),
    array('currency_code' => 'LVL', 'country_name' =>'Latvia, Lati'),
    array('currency_code' => 'LYD', 'country_name' =>'Libya, Dinars'),
    array('currency_code' => 'MAD', 'country_name' =>'Morocco, Dirhams'),
    array('currency_code' => 'MDL', 'country_name' =>'Moldova, Lei'),
    array('currency_code' => 'MGA', 'country_name' =>'Madagascar, Ariary'),
    array('currency_code' => 'MKD', 'country_name' =>'Macedonia, Denars'),
    array('currency_code' => 'MMK', 'country_name' =>'Myanmar (Burma), Kyats'),
    array('currency_code' => 'MNT', 'country_name' =>'Mongolia, Tugriks'),
    array('currency_code' => 'MOP', 'country_name' =>'Macau, Patacas'),
    array('currency_code' => 'MRO', 'country_name' =>'Mauritania, Ouguiyas'),
    array('currency_code' => 'MTL', 'country_name' =>'Malta, Liri (expires 2008-Jan-31)'),
    array('currency_code' => 'MUR', 'country_name' =>'Mauritius, Rupees'),
    array('currency_code' => 'MVR', 'country_name' =>'Maldives (Maldive Islands), Rufiyaa'),
    array('currency_code' => 'MWK', 'country_name' =>'Malawi, Kwachas'),
    array('currency_code' => 'MXN', 'country_name' =>'Mexico, Pesos'),
    array('currency_code' => 'MYR', 'country_name' =>'Malaysia, Ringgits'),
    array('currency_code' => 'MZN', 'country_name' =>'Mozambique, Meticais'),
    array('currency_code' => 'NAD', 'country_name' =>'Namibia, Dollars'),
    array('currency_code' => 'NGN', 'country_name' =>'Nigeria, Nairas'),
    array('currency_code' => 'NIO', 'country_name' =>'Nicaragua, Cordobas'),
    array('currency_code' => 'NOK', 'country_name' =>'Norway, Krone'),
    array('currency_code' => 'NPR', 'country_name' =>'Nepal, Nepal Rupees'),
    array('currency_code' => 'NZD', 'country_name' =>'New Zealand, Dollars'),
    array('currency_code' => 'OMR', 'country_name' =>'Oman, Rials'),
    array('currency_code' => 'PAB', 'country_name' =>'Panama, Balboa'),
    array('currency_code' => 'PEN', 'country_name' =>'Peru, Nuevos Soles'),
    array('currency_code' => 'PGK', 'country_name' =>'Papua New Guinea, Kina'),
    array('currency_code' => 'PHP', 'country_name' =>'Philippines, Pesos'),
    array('currency_code' => 'PKR', 'country_name' =>'Pakistan, Rupees'),
    array('currency_code' => 'PLN', 'country_name' =>'Poland, Zlotych'),
    array('currency_code' => 'PYG', 'country_name' =>'Paraguay, Guarani'),
    array('currency_code' => 'QAR', 'country_name' =>'Qatar, Rials'),
    array('currency_code' => 'RON', 'country_name' =>'Romania, New Lei'),
    array('currency_code' => 'RSD', 'country_name' =>'Serbia, Dinars'),
    array('currency_code' => 'RUB', 'country_name' =>'Russia, Rubles'),
    array('currency_code' => 'RWF', 'country_name' =>'Rwanda, Rwanda Francs'),
    array('currency_code' => 'SAR', 'country_name' =>'Saudi Arabia, Riyals'),
    array('currency_code' => 'SBD', 'country_name' =>'Solomon Islands, Dollars'),
    array('currency_code' => 'SCR', 'country_name' =>'Seychelles, Rupees'),
    array('currency_code' => 'SDG', 'country_name' =>'Sudan, Pounds'),
    array('currency_code' => 'SEK', 'country_name' =>'Sweden, Kronor'),
    array('currency_code' => 'SGD', 'country_name' =>'Singapore, Dollars'),
    array('currency_code' => 'SHP', 'country_name' =>'Saint Helena, Pounds'),
    array('currency_code' => 'SLL', 'country_name' =>'Sierra Leone, Leones'),
    array('currency_code' => 'SOS', 'country_name' =>'Somalia, Shillings'),
    array('currency_code' => 'SPL', 'country_name' =>'Seborga, Luigini'),
    array('currency_code' => 'SRD', 'country_name' =>'Suriname, Dollars'),
    array('currency_code' => 'STD', 'country_name' =>'São Tome and Principe, Dobras'),
    array('currency_code' => 'SVC', 'country_name' =>'El Salvador, Colones'),
    array('currency_code' => 'SYP', 'country_name' =>'Syria, Pounds'),
    array('currency_code' => 'SZL', 'country_name' =>'Swaziland, Emalangeni'),
    array('currency_code' => 'THB', 'country_name' =>'Thailand, Baht'),
    array('currency_code' => 'TJS', 'country_name' =>'Tajikistan, Somoni'),
    array('currency_code' => 'TMM', 'country_name' =>'Turkmenistan, Manats'),
    array('currency_code' => 'TND', 'country_name' =>'Tunisia, Dinars'),
    array('currency_code' => 'TOP', 'country_name' =>	'Tonga, Pa\'anga'),
    array('currency_code' => 'TRY', 'country_name' =>'Turkey, New Lira'),
    array('currency_code' => 'TTD', 'country_name' =>'Trinidad and Tobago, Dollars'),
    array('currency_code' => 'TVD', 'country_name' =>'Tuvalu, Tuvalu Dollars'),
    array('currency_code' => 'TWD', 'country_name' =>'Taiwan, New Dollars'),
    array('currency_code' => 'TZS', 'country_name' =>'Tanzania, Shillings'),
    array('currency_code' => 'UAH', 'country_name' =>'Ukraine, Hryvnia'),
    array('currency_code' => 'UGX', 'country_name' =>'Uganda, Shillings'),
    array('currency_code' => 'USD', 'country_name' =>'United States of America, Dollars'),
    array('currency_code' => 'UYU', 'country_name' =>'Uruguay, Pesos'),
    array('currency_code' => 'UZS', 'country_name' =>'Uzbekistan, Sums'),
    array('currency_code' => 'VEB', 'country_name' =>'Venezuela, Bolivares (expires 2008-Jun-30)'),
    array('currency_code' => 'VEF', 'country_name' =>'Venezuela, Bolivares Fuertes'),
    array('currency_code' => 'VND', 'country_name' =>'Viet Nam, Dong'),
    array('currency_code' => 'VUV', 'country_name' =>'Vanuatu, Vatu'),
    array('currency_code' => 'WST', 'country_name' =>'Samoa, Tala'),
    array('currency_code' => 'XAF', 'country_name' =>'Communauté Financière Africaine BEAC, Francs'),
    array('currency_code' => 'XAG', 'country_name' =>'Silver, Ounces'),
    array('currency_code' => 'XAU', 'country_name' =>'Gold, Ounces'),
    array('currency_code' => 'XCD', 'country_name' =>'East Caribbean Dollars'),
    array('currency_code' => 'XDR', 'country_name' =>'International Monetary Fund (IMF) Special Drawing Rights'),
    array('currency_code' => 'XOF', 'country_name' =>'Communauté Financière Africaine BCEAO, Francs'),
    array('currency_code' => 'XPD', 'country_name' =>'Palladium Ounces'),
    array('currency_code' => 'XPF', 'country_name' =>'Comptoirs Français du Pacifique Francs'),
    array('currency_code' => 'XPT', 'country_name' =>'Platinum, Ounces'),
    array('currency_code' => 'YER', 'country_name' =>'Yemen, Rials'),
    array('currency_code' => 'ZAR', 'country_name' =>'South Africa, Rand'),
    array('currency_code' => 'ZMK', 'country_name' =>'Zambia, Kwacha'),
    array('currency_code' => 'ZWD', 'country_name' =>'Zimbabwe, Zimbabwe Dollars'),
  );

foreach ($currency_code_data as $tmp) {
  $obj = new CurrencyCode(Globals::$dbaccess, $tmp);
  foreach ($tmp as $key => $val) {
    if (isset($obj->$key) && $obj->has_prop($key, 'immutable')) {
      continue; // skip
    }
    $obj->$key = $val;
  }
  $obj->save();
  // $obj_ar[$tmp['key-name']] = $obj;
}

$country_code_data = array(
    array('country_code' => 'AF', 'country_name' => 'AFGHANISTAN'),
    array('country_code' => 'AX', 'country_name' => 'ÅLAND ISLANDS'),
    array('country_code' => 'AL', 'country_name' => 'ALBANIA'),
    array('country_code' => 'DZ', 'country_name' => 'ALGERIA'),
    array('country_code' => 'AS', 'country_name' => 'AMERICAN SAMOA'),
    array('country_code' => 'AD', 'country_name' => 'ANDORRA'),
    array('country_code' => 'AO', 'country_name' => 'ANGOLA'),
    array('country_code' => 'AI', 'country_name' => 'ANGUILLA'),
    array('country_code' => 'AQ', 'country_name' => 'ANTARCTICA'),
    array('country_code' => 'AG', 'country_name' => 'ANTIGUA AND BARBUDA'),
    array('country_code' => 'AR', 'country_name' => 'ARGENTINA'),
    array('country_code' => 'AM', 'country_name' => 'ARMENIA'),
    array('country_code' => 'AW', 'country_name' => 'ARUBA'),
    array('country_code' => 'AU', 'country_name' => 'AUSTRALIA'),
    array('country_code' => 'AT', 'country_name' => 'AUSTRIA'),
    array('country_code' => 'AZ', 'country_name' => 'AZERBAIJAN'),

    array('country_code' => 'BS', 'country_name' => 'BAHAMAS'),
    array('country_code' => 'BH', 'country_name' => 'BAHRAIN'),
    array('country_code' => 'BD', 'country_name' => 'BANGLADESH'),
    array('country_code' => 'BB', 'country_name' => 'BARBADOS'),
    array('country_code' => 'BY', 'country_name' => 'BELARUS'),
    array('country_code' => 'BE', 'country_name' => 'BELGIUM'),
    array('country_code' => 'BZ', 'country_name' => 'BELIZE'),
    array('country_code' => 'BJ', 'country_name' => 'BENIN'),
    array('country_code' => 'BM', 'country_name' => 'BERMUDA'),
    array('country_code' => 'BT', 'country_name' => 'BHUTAN'),
    array('country_code' => 'BO', 'country_name' => 'BOLIVIA, PLURINATIONAL STATE OF'),
    array('country_code' => 'BA', 'country_name' => 'BOSNIA AND HERZEGOVINA'),
    array('country_code' => 'BW', 'country_name' => 'BOTSWANA'),
    array('country_code' => 'BV', 'country_name' => 'BOUVET ISLAND'),
    array('country_code' => 'BR', 'country_name' => 'BRAZIL'),
    array('country_code' => 'IO', 'country_name' => 'BRITISH INDIAN OCEAN TERRITORY'),
    array('country_code' => 'BN', 'country_name' => 'BRUNEI DARUSSALAM'),
    array('country_code' => 'BG', 'country_name' => 'BULGARIA'),
    array('country_code' => 'BF', 'country_name' => 'BURKINA FASO'),
    array('country_code' => 'BI', 'country_name' => 'BURUNDI'),

    array('country_code' => 'KH', 'country_name' => 'CAMBODIA'),
    array('country_code' => 'CM', 'country_name' => 'CAMEROON'),
    array('country_code' => 'CA', 'country_name' => 'CANADA'),
    array('country_code' => 'CV', 'country_name' => 'CAPE VERDE'),
    array('country_code' => 'KY', 'country_name' => 'CAYMAN ISLANDS'),
    array('country_code' => 'CF', 'country_name' => 'CENTRAL AFRICAN REPUBLIC'),
    array('country_code' => 'TD', 'country_name' => 'CHAD'),
    array('country_code' => 'CL', 'country_name' => 'CHILE'),
    array('country_code' => 'CN', 'country_name' => 'CHINA'),
    array('country_code' => 'CX', 'country_name' => 'CHRISTMAS ISLAND'),
    array('country_code' => 'CC', 'country_name' => 'COCOS (KEELING) ISLANDS'),
    array('country_code' => 'CO', 'country_name' => 'COLOMBIA'),
    array('country_code' => 'KM', 'country_name' => 'COMOROS'),
    array('country_code' => 'CG', 'country_name' => 'CONGO'),
    array('country_code' => 'CD', 'country_name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE'),
    array('country_code' => 'CK', 'country_name' => 'COOK ISLANDS'),
    array('country_code' => 'CR', 'country_name' => 'COSTA RICA'),
    array('country_code' => 'CI', 'country_name' => 'CÔTE D\'IVOIRE'),
    array('country_code' => 'HR', 'country_name'  =>'CROATIA'),
    array('country_code' => 'CU', 'country_name'  =>'CUBA'),
    array('country_code' => 'CY', 'country_name'  =>'CYPRUS'),
    array('country_code' => 'CZ', 'country_name'  =>'CZECH REPUBLIC'),

    array('country_code' => 'DK', 'country_name' =>'DENMARK'),
    array('country_code' => 'DJ', 'country_name' =>'DJIBOUTI'),
    array('country_code' => 'DM', 'country_name' =>'DOMINICA'),
    array('country_code' => 'DO', 'country_name' =>'DOMINICAN REPUBLIC'),

    array('country_code' => 'EC', 'country_name' =>'ECUADOR'),
    array('country_code' => 'EG', 'country_name' =>'EGYPT'),
    array('country_code' => 'SV', 'country_name' =>'EL SALVADOR'),
    array('country_code' => 'GQ', 'country_name' =>'EQUATORIAL GUINEA'),
    array('country_code' => 'ER', 'country_name' =>'ERITREA'),
    array('country_code' => 'EE', 'country_name' =>'ESTONIA'),
    array('country_code' => 'ET', 'country_name' =>'ETHIOPIA'),

    array('country_code' => 'FK', 'country_name' =>'FALKLAND ISLANDS (MALVINAS)'),
    array('country_code' => 'FO', 'country_name' =>'FAROE ISLANDS'),
    array('country_code' => 'FJ', 'country_name' =>'FIJI'),
    array('country_code' => 'FI', 'country_name' =>'FINLAND'),
    array('country_code' => 'FR', 'country_name' =>'FRANCE'),
    array('country_code' => 'GF', 'country_name' =>'FRENCH GUIANA'),
    array('country_code' => 'PF', 'country_name' =>'FRENCH POLYNESIA'),
    array('country_code' => 'TF', 'country_name' =>'FRENCH SOUTHERN TERRITORIES'),

    array('country_code' => 'GA', 'country_name' =>'GABON'),
    array('country_code' => 'GM', 'country_name' =>'GAMBIA'),
    array('country_code' => 'GE', 'country_name' =>'GEORGIA'),
    array('country_code' => 'DE', 'country_name' =>'GERMANY'),
    array('country_code' => 'GH', 'country_name' =>'GHANA'),
    array('country_code' => 'GI', 'country_name' =>'GIBRALTAR'),
    array('country_code' => 'GR', 'country_name' =>'GREECE'),
    array('country_code' => 'GL', 'country_name' =>'GREENLAND'),
    array('country_code' => 'GD', 'country_name' =>'GRENADA'),
    array('country_code' => 'GP', 'country_name' =>'GUADELOUPE'),
    array('country_code' => 'GU', 'country_name' =>'GUAM'),
    array('country_code' => 'GT', 'country_name' =>'GUATEMALA'),
    array('country_code' => 'GG', 'country_name' =>'GUERNSEY'),
    array('country_code' => 'GN', 'country_name' =>'GUINEA'),
    array('country_code' => 'GW', 'country_name' =>'GUINEA-BISSAU'),
    array('country_code' => 'GY', 'country_name' =>'GUYANA'),

    array('country_code' => 'HT', 'country_name' =>'HAITI'),
    array('country_code' => 'HM', 'country_name' =>'HEARD ISLAND AND MCDONALD ISLANDS'),
    array('country_code' => 'VA', 'country_name' =>'HOLY SEE (VATICAN CITY STATE)'),
    array('country_code' => 'HN', 'country_name' =>'HONDURAS'),
    array('country_code' => 'HK', 'country_name' =>'HONG KONG'),
    array('country_code' => 'HU', 'country_name' =>'HUNGARY'),

    array('country_code' => 'IS', 'country_name' =>'ICELAND'),
    array('country_code' => 'IN', 'country_name' =>'INDIA'),
    array('country_code' => 'ID', 'country_name' =>'INDONESIA'),
    array('country_code' => 'IR', 'country_name' =>'IRAN, ISLAMIC REPUBLIC OF'),
    array('country_code' => 'IQ', 'country_name' =>'IRAQ'),
    array('country_code' => 'IE', 'country_name' =>'IRELAND'),
    array('country_code' => 'IM', 'country_name' =>'ISLE OF MAN'),
    array('country_code' => 'IL', 'country_name' =>'ISRAEL'),
    array('country_code' => 'IT', 'country_name' =>'ITALY'),

    array('country_code' => 'JM', 'country_name' =>'JAMAICA'),
    array('country_code' => 'JP', 'country_name' =>'JAPAN'),
    array('country_code' => 'JE', 'country_name' =>'JERSEY'),
    array('country_code' => 'JO', 'country_name' =>'JORDAN'),

    array('country_code' => 'KZ', 'country_name' =>'KAZAKHSTAN'),
    array('country_code' => 'KE', 'country_name' =>'KENYA'),
    array('country_code' => 'KI', 'country_name' =>'KIRIBATI'),
    array('country_code' => 'KP', 'country_name' =>'KOREA, DEMOCRATIC PEOPLE\'S REPUBLIC OF'),
    array('country_code' => 'KR', 'country_name' =>'KOREA, REPUBLIC OF'),
    array('country_code' => 'KW', 'country_name' =>'KUWAIT'),
    array('country_code' => 'KG', 'country_name' =>'KYRGYZSTAN'),

    array('country_code' => 'LA', 'country_name'  => 'LAO PEOPLE\'S DEMOCRATIC REPUBLIC'),
    array('country_code' => 'LV', 'country_name' =>'LATVIA'),
    array('country_code' => 'LB', 'country_name' =>'LEBANON'),
    array('country_code' => 'LS', 'country_name' =>'LESOTHO'),
    array('country_code' => 'LR', 'country_name' =>'LIBERIA'),
    array('country_code' => 'LY', 'country_name' =>'LIBYAN ARAB JAMAHIRIYA'),
    array('country_code' => 'LI', 'country_name' =>'LIECHTENSTEIN'),
    array('country_code' => 'LT', 'country_name' =>'LITHUANIA'),
    array('country_code' => 'LU', 'country_name' =>'LUXEMBOURG'),

    array('country_code' => 'MO', 'country_name' =>'MACAO'),
    array('country_code' => 'MK', 'country_name' =>'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF'),
    array('country_code' => 'MG', 'country_name' =>'MADAGASCAR'),
    array('country_code' => 'MW', 'country_name' =>'MALAWI'),
    array('country_code' => 'MY', 'country_name' =>'MALAYSIA'),
    array('country_code' => 'MV', 'country_name' =>'MALDIVES'),
    array('country_code' => 'ML', 'country_name' =>'MALI'),
    array('country_code' => 'MT', 'country_name' =>'MALTA'),
    array('country_code' => 'MH', 'country_name' =>'MARSHALL ISLANDS'),
    array('country_code' => 'MQ', 'country_name' =>'MARTINIQUE'),
    array('country_code' => 'MR', 'country_name' =>'MAURITANIA'),
    array('country_code' => 'MU', 'country_name' =>'MAURITIUS'),
    array('country_code' => 'YT', 'country_name' =>'MAYOTTE'),
    array('country_code' => 'MX', 'country_name' =>'MEXICO'),
    array('country_code' => 'FM', 'country_name' =>'MICRONESIA, FEDERATED STATES OF'),
    array('country_code' => 'MD', 'country_name' =>'MOLDOVA, REPUBLIC OF'),
    array('country_code' => 'MC', 'country_name' =>'MONACO'),
    array('country_code' => 'MN', 'country_name' =>'MONGOLIA'),
    array('country_code' => 'ME', 'country_name' =>'MONTENEGRO'),
    array('country_code' => 'MS', 'country_name' =>'MONTSERRAT'),
    array('country_code' => 'MA', 'country_name' =>'MOROCCO'),
    array('country_code' => 'MZ', 'country_name' =>'MOZAMBIQUE'),
    array('country_code' => 'MM', 'country_name' =>'MYANMAR'),

    array('country_code' => 'NA', 'country_name' =>'NAMIBIA'),
    array('country_code' => 'NR', 'country_name' =>'NAURU'),
    array('country_code' => 'NP', 'country_name' =>'NEPAL'),
    array('country_code' => 'NL', 'country_name' =>'NETHERLANDS'),
    array('country_code' => 'AN', 'country_name' =>'NETHERLANDS ANTILLES'),
    array('country_code' => 'NC', 'country_name' =>'NEW CALEDONIA'),
    array('country_code' => 'NZ', 'country_name' =>'NEW ZEALAND'),
    array('country_code' => 'NI', 'country_name' =>'NICARAGUA'),
    array('country_code' => 'NE', 'country_name' =>'NIGER'),
    array('country_code' => 'NG', 'country_name' =>'NIGERIA'),
    array('country_code' => 'NU', 'country_name' =>'NIUE'),
    array('country_code' => 'NF', 'country_name' =>'NORFOLK ISLAND'),
    array('country_code' => 'MP', 'country_name' =>'NORTHERN MARIANA ISLANDS'),
    array('country_code' => 'NO', 'country_name' =>'NORWAY'),

    array('country_code' => 'OM', 'country_name' =>'OMAN'),

    array('country_code' => 'PK', 'country_name' =>'PAKISTAN'),
    array('country_code' => 'PW', 'country_name' =>'PALAU'),
    array('country_code' => 'PS', 'country_name' =>'PALESTINIAN TERRITORY, OCCUPIED'),
    array('country_code' => 'PA', 'country_name' =>'PANAMA'),
    array('country_code' => 'PG', 'country_name' =>'PAPUA NEW GUINEA'),
    array('country_code' => 'PY', 'country_name' =>'PARAGUAY'),
    array('country_code' => 'PE', 'country_name' =>'PERU'),
    array('country_code' => 'PH', 'country_name' =>'PHILIPPINES'),
    array('country_code' => 'PN', 'country_name' =>'PITCAIRN'),
    array('country_code' => 'PL', 'country_name' =>'POLAND'),
    array('country_code' => 'PT', 'country_name' =>'PORTUGAL'),
    array('country_code' => 'PR', 'country_name' =>'PUERTO RICO'),

    array('country_code' => 'QA', 'country_name' =>'QATAR'),

    array('country_code' => 'RE', 'country_name' =>'RÉUNION'),
    array('country_code' => 'RO', 'country_name' =>'ROMANIA'),
    array('country_code' => 'RU', 'country_name' =>'RUSSIAN FEDERATION'),
    array('country_code' => 'RW', 'country_name' =>'RWANDA'),

    array('country_code' => 'BL', 'country_name' =>'SAINT BARTHÉLEMY'),
    array('country_code' => 'SH', 'country_name' =>'SAINT HELENA, ASCENSION AND TRISTAN DA CUNHA'),
    array('country_code' => 'KN', 'country_name' =>'SAINT KITTS AND NEVIS'),
    array('country_code' => 'LC', 'country_name' =>'SAINT LUCIA'),
    array('country_code' => 'MF', 'country_name' =>'SAINT MARTIN'),
    array('country_code' => 'PM', 'country_name' =>'SAINT PIERRE AND MIQUELON'),
    array('country_code' => 'VC', 'country_name' =>'SAINT VINCENT AND THE GRENADINES'),
    array('country_code' => 'WS', 'country_name' =>'SAMOA'),
    array('country_code' => 'SM', 'country_name' =>'SAN MARINO'),
    array('country_code' => 'ST', 'country_name' =>'SAO TOME AND PRINCIPE'),
    array('country_code' => 'SA', 'country_name' =>'SAUDI ARABIA'),
    array('country_code' => 'SN', 'country_name' =>'SENEGAL'),
    array('country_code' => 'RS', 'country_name' =>'SERBIA'),
    array('country_code' => 'SC', 'country_name' =>'SEYCHELLES'),
    array('country_code' => 'SL', 'country_name' =>'SIERRA LEONE'),
    array('country_code' => 'SG', 'country_name' =>'SINGAPORE'),
    array('country_code' => 'SK', 'country_name' =>'SLOVAKIA'),
    array('country_code' => 'SI', 'country_name' =>'SLOVENIA'),
    array('country_code' => 'SB', 'country_name' =>'SOLOMON ISLANDS'),
    array('country_code' => 'SO', 'country_name' =>'SOMALIA'),
    array('country_code' => 'ZA', 'country_name' =>'SOUTH AFRICA'),
    array('country_code' => 'GS', 'country_name' =>'SOUTH GEORGIA AND THE SOUTH SANDWICH ISLANDS'),
    array('country_code' => 'ES', 'country_name' =>'SPAIN'),
    array('country_code' => 'LK', 'country_name' =>'SRI LANKA'),
    array('country_code' => 'SD', 'country_name' =>'SUDAN'),
    array('country_code' => 'SR', 'country_name' =>'SURINAME'),
    array('country_code' => 'SJ', 'country_name' =>'SVALBARD AND JAN MAYEN'),
    array('country_code' => 'SZ', 'country_name' =>'SWAZILAND'),
    array('country_code' => 'SE', 'country_name' =>'SWEDEN'),
    array('country_code' => 'CH', 'country_name' =>'SWITZERLAND'),
    array('country_code' => 'SY', 'country_name' =>'SYRIAN ARAB REPUBLIC'),

    array('country_code' => 'TW', 'country_name' =>'TAIWAN, PROVINCE OF CHINA'),
    array('country_code' => 'TJ', 'country_name' =>'TAJIKISTAN'),
    array('country_code' => 'TZ', 'country_name' =>'TANZANIA, UNITED REPUBLIC OF'),
    array('country_code' => 'TH', 'country_name' =>'THAILAND'),
    array('country_code' => 'TL', 'country_name' =>'TIMOR-LESTE'),
    array('country_code' => 'TG', 'country_name' =>'TOGO'),
    array('country_code' => 'TK', 'country_name' =>'TOKELAU'),
    array('country_code' => 'TO', 'country_name' =>'TONGA'),
    array('country_code' => 'TT', 'country_name' =>'TRINIDAD AND TOBAGO'),
    array('country_code' => 'TN', 'country_name' =>'TUNISIA'),
    array('country_code' => 'TR', 'country_name' =>'TURKEY'),
    array('country_code' => 'TM', 'country_name' =>'TURKMENISTAN'),
    array('country_code' => 'TC', 'country_name' =>'TURKS AND CAICOS ISLANDS'),
    array('country_code' => 'TV', 'country_name' =>'TUVALU'),

    array('country_code' => 'UG', 'country_name' =>'UGANDA'),
    array('country_code' => 'UA', 'country_name' =>'UKRAINE'),
    array('country_code' => 'AE', 'country_name' =>'UNITED ARAB EMIRATES'),
    array('country_code' => 'GB', 'country_name' =>'UNITED KINGDOM'),
    array('country_code' => 'US', 'country_name' =>'UNITED STATES'),
    array('country_code' => 'UM', 'country_name' =>'UNITED STATES MINOR OUTLYING ISLANDS'),
    array('country_code' => 'UY', 'country_name' =>'URUGUAY'),
    array('country_code' => 'UZ', 'country_name' =>'UZBEKISTAN'),

    array('country_code' => 'VU', 'country_name' =>'VANUATU'),
    array('country_code' => 'VE', 'country_name' =>'VENEZUELA, BOLIVARIAN REPUBLIC OF'),
    array('country_code' => 'VN', 'country_name' =>'VIET NAM'),
    array('country_code' => 'VG', 'country_name' =>'VIRGIN ISLANDS, BRITISH'),
    array('country_code' => 'VI', 'country_name' =>'VIRGIN ISLANDS, U.S.'),

    array('country_code' => 'WF', 'country_name' =>'WALLIS AND FUTUNA'),
    array('country_code' => 'EH', 'country_name' =>'WESTERN SAHARA'),

    array('country_code' => 'YE', 'country_name' =>'YEMEN'),

    array('country_code' => 'ZM', 'country_name' =>'ZAMBIA'),
    array('country_code' => 'ZW', 'country_name' =>'ZIMBABWE'),
  );

foreach ($country_code_data as $tmp) {
  $obj = new CountryCode(Globals::$dbaccess, $tmp);
  foreach ($tmp as $key => $val) {
    if (isset($obj->$key) && $obj->has_prop($key, 'immutable')) {
      continue; // skip
    }
    $obj->$key = $val;
  }
  $obj->save();
  // $obj_ar[$tmp['key-name']] = $obj;
}