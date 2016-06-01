<?php

/*
 * alias_login
 *
 * Allow a use to login with multiple aliases stored in the login_aliases
 * table.
 *
 * Author: Sebastiaan Hoogeveen
 * License: GPLv3
 *
 */

class alias_login extends rcube_plugin
{

  function init()
  {
    $this->add_hook('authenticate', array($this, 'authenticate'));
  }

  function authenticate($args) {

    $this->load_config();
    $rcmail = rcmail::get_instance();
    $db = $rcmail->db;
    $res = $db->query('SELECT DISTINCT(username) FROM users JOIN login_aliases ON users.user_id = login_aliases.user_id WHERE login_aliases.alias = ? LIMIT 2;', $args['user']);
    if ( $db->num_rows() == 1 ) {
      $user = $db->fetch_array($res);
      rcube::write_log('alias_login', 'Used alias ' . $args['user'] . ' to login as ' . $user[0]);
      $args['user'] = $user[0];
    }

    return $args;

  }
}
?>
