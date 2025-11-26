<?php
namespace Custom\Reviews\Component\Lib;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserTable;

Loc::loadMessages(__FILE__);

class ReviewsTable extends DataManager
{
    public static function getTableName()
    {
        return 'custom_reviews';
    }
    
    public static function getMap()
    {
        return [
            (new Fields\IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
                
            (new Fields\StringField('AUTHOR'))
                ->configureRequired(true)
                ->configureSize(100),
                
            (new Fields\TextField('TEXT'))
                ->configureRequired(true),
                
            (new Fields\IntegerField('RATING'))
                ->configureRequired(true)
                ->addValidator(function($value) {
                    if ($value < 1 || $value > 5) {
                        return 'Rating must be between 1 and 5';
                    }
                    return true;
                }),
                
            (new Fields\DatetimeField('CREATED_AT'))
                ->configureDefaultValue(function() {
                    return new \Bitrix\Main\Type\DateTime();
                }),
                
            (new Fields\IntegerField('USER_ID')),
                
            // Связь с пользователем (опционально)
            (new Fields\Relations\Reference(
                'USER',
                UserTable::class,
                Join::on('this.USER_ID', 'ref.ID')
            ))->configureJoinType('LEFT'),
        ];
    }
    
    public static function createTable()
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $sql = "
            CREATE TABLE IF NOT EXISTS `".self::getTableName()."` (
                `ID` INT NOT NULL AUTO_INCREMENT,
                `AUTHOR` VARCHAR(100) NOT NULL,
                `TEXT` TEXT NOT NULL,
                `RATING` INT NOT NULL CHECK (RATING >= 1 AND RATING <= 5),
                `CREATED_AT` DATETIME NOT NULL,
                `USER_ID` INT NULL,
                PRIMARY KEY (ID),
                INDEX ix_created_at (CREATED_AT)
            )
        ";
        $connection->query($sql);
    }
}
