<?php

require_once(Config::get('CONTROLLER_DIR') . 'admin/admin_controller.php');

class BlogSettingsController extends AdminController
{

  /**
   * コメント編集
   */
  public function comment_edit()
  {
    $white_list = array(
      'comment_confirm', 'comment_display_approval', 'comment_display_private',
      'comment_cookie_save', 'comment_captcha',
      'comment_order', 'comment_display_count', 'comment_quote'
    );
    $this->settingEdit($white_list, 'comment_edit');
  }

  /**
   * 記事編集
   */
  public function entry_edit()
  {
    $white_list = array('entry_order', 'entry_recent_display_count', 'entry_display_count', 'entry_password');
    $this->settingEdit($white_list, 'entry_edit');
  }

  /**
   * その他編集
   */
  public function etc_edit()
  {
    $white_list = array('start_page');
    $this->settingEdit($white_list, 'etc_edit');
  }

  /**
  * ブログの設定変更処理
  */
  private function settingEdit($white_list, $action)
  {
    $request = Request::getInstance();
    $blog_settings_model = Model::load('BlogSettings');

    $blog_id = $this->getBlogId();

    // 初期表示時に編集データの取得&設定
    if (!$request->get('blog_setting') || !Session::get('sig') || Session::get('sig') !== $request->get('sig')) {
      Session::set('sig', App::genRandomString());
      $blog_setting = $blog_settings_model->findByBlogId($blog_id);
      $request->set('blog_setting', $blog_setting);
      return ;
    }

    // 更新処理
    $errors = array();
    $errors['blog_setting'] = $blog_settings_model->validate($request->get('blog_setting'), $blog_setting_data, $white_list);
    if (empty($errors['blog_setting'])) {
      // コメント確認からコメントを確認せずそのまま表示に変更した場合既存の承認待ちを全て承認済みに変更する
      $blog_setting = $blog_settings_model->findByBlogId($blog_id);
      if ($blog_setting['comment_confirm'] == Config::get('COMMENT.COMMENT_CONFIRM.CONFIRM')
        && $blog_setting_data['comment_confirm'] == Config::get('COMMENT.COMMENT_CONFIRM.THROUGH')
      ) {
        Model::load('Comments')->updateApproval($blog_id);
      }

      // ブログの設定情報更新処理
      if ($blog_settings_model->updateByBlogId($blog_setting_data, $blog_id)) {
        // 一覧ページへ遷移
        $this->setInfoMessage(__("I have updated the configuration information of the blog"));
        $this->redirect(array('action'=>$action));
      }
    }

    // エラー情報の設定
    $this->setErrorMessage(__('Input error exists'));
    $this->set('errors', $errors);
  }

}

