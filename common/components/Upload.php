<?php
/**
 * Created by PhpStorm.
 * User: alex
 * Date: 2017/6/27
 * Time: 下午4:05
 */

namespace common\components;


use Yii;
use yii\base\Model;
use yii\web\UploadedFile;
use yii\base\Exception;
use yii\helpers\FileHelper;
use common\service\Service;


/**
 * 文件上传处理
 */
class Upload extends Model
{
	public $file;


	private $_appendRules;


	public function init ()
	{
		parent::init();
		$extensions = Yii::$app->params['webuploader']['baseConfig']['accept']['extensions'];
		$this->_appendRules = [
			[['file'], 'file', 'extensions' => $extensions],
		];
	}


	public function rules()
	{
		$baseRules = [];
		return array_merge($baseRules, $this->_appendRules);
	}


	/**
	 *
	 */
	public function upImage ()
	{
		$model = new static;
		$model->file = UploadedFile::getInstanceByName('file');
		if (!$model->file) {
			return false;
		}


		$relativePath = $successPath = '';


		if ($model->validate()) {
			$relativePath = Yii::$app->params['imageUploadRelativePath'].date('Ymd').'/';
			$successPath = Yii::$app->params['imageUploadSuccessPath'];
			$img_id = date('Ymd').substr(Service::create_uuid(),0,8);
			//$fileName = $model->file->baseName . '.' . $model->file->extension;
			$fileName = $img_id . '.' . $model->file->extension;


			if (!is_dir($relativePath)) {
				FileHelper::createDirectory($relativePath);
			}

			$model->file->saveAs($relativePath . $fileName);


			return [
				'code' => 0,
				'url' => Yii::$app->params['domain'] . $successPath . $fileName,
				'attachment' => $successPath . $fileName
			];


		} else {
			$errors = $model->errors;
			return [
				'code' => 1,
				'msg' => current($errors)[0]
			];
		}
	}
}