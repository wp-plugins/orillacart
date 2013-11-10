<div class="com_shop">    <form target="_self" name="adminForm" action="<?php echo admin_url('admin.php?page=component_com_shop-tax'); ?>" method="post">         <input type='hidden' name='task' value='save_rate' />        <input type="hidden" value="<?php echo (int) $this->tax_group_id; ?>" name="tax_group_id">        <input type="hidden" value="<?php echo strings::show($this->row->tax_rate_id, '', false, 'int'); ?>" name="tax_rate_id">        <fieldset class="panelform">            <ul class="adminformlist">                <li>                    <label for="tax_country">                        <?php _e('Vat Country:', 'com_shop'); ?>                    </label>                    <select name="tax_country" id="tax_country" onchange='jsShopAdminHelper.loadStates(this.value, "states_container");'>                        <option value=''><?php _e('select country', 'com_shop'); ?></option>                        <?php foreach ((array) $this->countries as $c) { ?>                            <option value='<?php echo $c->country_2_code; ?>' <?php echo (strings::show($this->row->tax_country, 0) == $c->country_2_code) ? "selected='selected'" : ""; ?>>                                <?php echo strings::stripAndEncode($c->country_name); ?>                            </option>                        <?php } ?>                    </select>                </li>                <li>                    <label for="tax_state">                        <?php _e('States:', 'com_shop'); ?>                    </label>                    <span id='states_container'>                        <select id="tax_state" name='tax_state'>                            <option value=''></option>                            <?php foreach ((array) $this->states as $s) { ?>                                <option value='<?php echo $s->state_2_code; ?>' <?php echo (strings::show($this->row->tax_state, null) == $s->state_2_code) ? "selected='selected'" : ''; ?>>                                    <?php echo strings::stripAndEncode($s->state_name); ?>                                </option>                            <?php } ?>                        </select>                    </span>                </li>                <li>                    <label for="tax_rate">                        <?php _e('Rate:', 'com_shop'); ?>                    </label>                    <input type='text' value='<?php echo strings::show($this->row->tax_rate, ''); ?>' id="tax_rate" name='tax_rate' />                </li>            </ul>        </fieldset>    </form></div>