<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\AdminLog */

$this->title = '查看操作日志';
$this->params['breadcrumbs'][] = ['label' => '操作日志', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="admin-log-view">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
//            'route',
//            'description:ntext',
//            'create_at',
//            'user_id',
//            'ip',

//            'user_id',
            [
                'attribute'=>'username',
                'label'=>'用户名',
                'value' => $model->adminuser->username,
            ],
//            'route',
            'description:ntext',

//            'create_at',
            [
                'attribute' => 'create_at',
                'format' => ['date', 'php:Y-m-d H:i:s'],
            ],

//             'ip',
            [
                'attribute' => 'ip',
                'value' => long2ip($model->ip),
            ],
        ],
    ]) ?>

</div>
