<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\web\IdentityInterface;
use yii\helpers\Security;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Html;
use backend\models\Role;
use backend\models\Status;
use backend\models\UserType;
use frontend\models\Profile;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_ACTIVE = 10;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
			'timestamp' => [
			'class' => 'yii\behaviors\TimestampBehavior',
			'attributes' => [
				ActiveRecord::EVENT_BEFORE_INSERT => ['created_at', 'updated_at'],
				ActiveRecord::EVENT_BEFORE_UPDATE => ['updated_at'],
							],
				'value' => new Expression('NOW()'),
							],
				];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['status_id', 'default', 'value' => self::STATUS_ACTIVE],
			[['status_id'],'in', 'range'=>array_keys($this->getStatusList())],
			['role_id', 'default', 'value' => 10],
			[['role_id'],'in', 'range'=>array_keys($this->getRoleList())],
			['user_type_id', 'default', 'value' => 10],
			[['user_type_id'],'in', 'range'=>array_keys($this->getUserTypeList())],
			['username', 'filter', 'filter' => 'trim'],
			['username', 'required'],
			['username', 'unique'],
			['username', 'string', 'min' => 2, 'max' => 255],
			['email', 'filter', 'filter' => 'trim'],
			['email', 'required'],
			['email', 'email'],
			['email', 'unique'],
        ];
    }
	
	/* Your model attribute labels */
	
	public function attributeLabels()
	{
		return [
		/* Your other attribute labels */
		
		'roleName' => Yii::t('app', 'Role'),
		'statusName' => Yii::t('app', 'Status'),
		'profileId' => Yii::t('app', 'Profile'),
		'profileLink' => Yii::t('app', 'Profile'),
		'userLink' => Yii::t('app', 'User'),
		'username' => Yii::t('app', 'User'),
		'userTypeName' => Yii::t('app', 'User Type'),
		'userTypeId' => Yii::t('app', 'User Type'),
		'userIdLink' => Yii::t('app', 'ID'),
				];
	}

    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status_id' => self::STATUS_ACTIVE]);
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status_id' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
       $expire = Yii::$app->params['user.passwordResetTokenExpire'];
		$parts = explode('_', $token);
		$timestamp = (int) end($parts);
		if ($timestamp + $expire < time()) {
			// token expired
		return null;
		}
		return static::findOne([
			'password_reset_token' => $token,
			'status_id' => self::STATUS_ACTIVE,
			]);
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
	
	/**
	 * get role relationship
	 *
	 */
	 
	public function getRole()
	{
		return $this->hasOne(Role::className(), ['role_value' => 'role_id']);
	}
	 
	/**
	 *get role name
	 *
	 */
	  
	public function getRoleName()
	{
		return $this->role ? $this->role->role_name : '- роль не найдена -';
	}
	
	/**
	 * get list of roles for dropdown
	 */
	 
	public static function getRoleList()
	{
		$droptions = Role::find()->asArray()->all();
		return Arrayhelper::map($droptions, 'role_value', 'role_name');
	}
	
	/**
	* get status relation
	*
	*/
	public function getStatus()
	{
		return $this->hasOne(Status::className(), ['status_value' => 'status_id']);
	}
	
	/**
	Chapter Five: Creating New Models with Gii 78
	* get status name
	*
	*/
	
	public function getStatusName()
	{
		return $this->status ? $this->status->status_name : '- без статуса -';
	}
	
	/**
	* get list of statuses for dropdown
	*/
	
	public static function getStatusList()
	{
		$droptions = Status::find()->asArray()->all();
		return Arrayhelper::map($droptions, 'status_value', 'status_name');
	}
	
	/**
	*getUserType
	*line break to avoid word wrap in PDF
	* code as single line in your IDE
	*/
	public function getUserType()
	{
		return $this->hasOne(UserType::className(), ['user_type_value' =>
		'user_type_id']);
	}
	
	/**
	* get user type name
	*
	*/
	public function getUserTypeName()
	{
		return $this->userType ? $this->userType->user_type_name : '- тип не указан -';
	}
	
	/**
	* get list of user types for dropdown
	*/
	public static function getUserTypeList()
	{
		$droptions = UserType::find()->asArray()->all();
		return Arrayhelper::map($droptions, 'user_type_value', 'user_type_name');
	}
	
	/**
	* get user type id
	*
	*/
	public function getUserTypeId()
	{
		return $this->userType ? $this->userType->id : 'none';
	}
	
	/**
	 * @getProfile
	 *
	 */
	public function getProfile()
	{
		return $this->hasOne(Profile::className(), ['user_id' => 'id']);
	}

	/**
	* @getProfileId
	*
	*/
	public function getProfileId()
	{
		return $this->profile ? $this->profile->id : 'none';
	}
	
	/**
	* @getProfileLink
	*
	*/
	public function getProfileLink()
	{
		$url = Url::to(['profile/view', 'id'=>$this->profileId]);
		$options = [];
		return Html::a($this->profile ? 'profile' : 'none', $url, $options);
	}
	
	/**
	* get user id Link
	*
	*/
	public function getUserIdLink()
	{
		$url = Url::to(['user/update', 'id'=>$this->id]);
		$options = [];
		return Html::a($this->id, $url, $options);
	}
	
	/**
	* @getUserLink
	*
	*/
	public function getUserLink()
	{
		$url = Url::to(['user/view', 'id'=>$this->Id]);
		$options = [];
		return Html::a($this->username, $url, $options);
	}

}
