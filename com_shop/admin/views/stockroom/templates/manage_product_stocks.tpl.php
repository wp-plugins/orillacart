<div class="com_shop">    <form action="<?php echo admin_url('admin.php?page=component_com_shop-stockroom-manage'); ?>" method="post" id='adminForm' name="adminForm">        <input type="hidden" name="task" value="" />        <table>            <tr>                <td valign="top" align="right" class="key">                    <select name="stockroom_type" id="stockroom_type" class="inputbox" size="1" onChange="jQuery('#catselect').css('display', 'none').prependTo('#adminForm');                            document.adminForm.submit();" >                        <option value="product" <?php echo (request::getWord('stockroom_type', 'product', 'POST') == 'product') ? "selected='selected'" : ''; ?> ><?php _e('Product', 'com_shop'); ?></option>                        <option value="product_attribute_property" <?php echo (request::getWord('stockroom_type', 'product', 'POST') == 'product_attribute_property') ? "selected='selected'" : ''; ?> > <?php _e('Product Attribute property', 'com_shop'); ?></option>                    </select>                </td>                <td valign="top" align="right" class="key">                    <select name="sr_id" id="stockroom" class="inputbox" size="1" onChange="jQuery('#catselect').css('display', 'none').prependTo('#adminForm');                            document.adminForm.submit();" >                        <option value='' <?php echo!request::getInt('sr_id', NULL, 'POST') ? "selected='selected'" : ''; ?> ><?php _e('--stockroom--', 'com_shop'); ?></option>                        <?php while ($o = $this->stockrooms->nextObject()) { ?>                            <option value='<?php echo $o->id; ?>'   <?php echo request::getInt('sr_id', NULL, 'POST') == $o->id ? "selected='selected'" : ''; ?>><?php echo strings::stripAndEncode($o->name); ?></option>                        <?php } ?>                    </select>                </td>                <td class="filter-qty-container">                    <button id="modal" href="#" onclick="" class="btn btn-small">                        <i class="icon-folder-2">                        </i>                        <?php _e('Category', 'com_shop'); ?>                    </button>                     <input type="text" placeholder="<?php _e('Search Term...', 'com_shop'); ?>" value="<?php echo request::getWord('keyword', '', 'POST') ?>" name="keyword">                    <input type="text" placeholder="<?php _e('Parent Product...', 'com_shop'); ?>" value="" id="select_parent" name="select_parent">                    <input type="hidden" name="parent_product" id="parent_product" value="<?php echo request::getInt('parent_product', 0); ?>" />                    <button href="#" onclick="document.adminForm.task.value = '';                            document.adminForm.submit();" class="btn btn-small">                        <i class="icon-search ">                        </i>                        <?php _e('search', 'com_shop'); ?>                    </button>                </td>            </tr>        </table>        <div id="editcell">            <table class="wp-list-table widefat fixed posts">                <thead>                    <tr>                        <th width="5%">#</th>                        <th width="15%"><?php _e('Product Number', 'com_shop'); ?></th>                        <th width="55%"><?php _e('Product Name', 'com_shop'); ?></th>                        <th width="20%"><?php _e('stock room name', 'com_shop'); ?></th>                        <th width="5%" align='center'>                            <img style="cursor:pointer" src="<?php echo Factory::getApplication('shop')->getAssetsUrl(); ?>/images/save-mini.png" onClick="jQuery('#catselect').css('display', 'none').prependTo('#adminForm');                            document.adminForm.action.value = 'save';                            document.adminForm.submit();"></th>                    </tr>                </thead>                <?php                $c = 0;                while ($o = $this->objects->nextObject()) {                    ?>                    <tr class="row<?php echo (int) (bool) ($c % 2); ?>">                        <td><?php echo $c; ?></td>                        <td><?php echo $o->sku; ?> </a></td>                        <td><a href='<?php echo get_edit_post_link($o->product_id); ?>'><?php echo empty($o->post_title) ? '...' : strings::stripAndEncode($o->post_title); ?></a></td>                        <td>                            <strong><?php echo $o->sr_name; ?></strong>                        </td>                        <td align="center"><input type="hidden" name='sid[]' value="<?php echo (int) $o->stockroom_id; ?>">                            <input type="hidden" name='pid[]' value="<?php echo (int) $o->product_id; ?>">                            <input type="text" value="<?php echo (int) $o->stock; ?>" name="quantity[]" size='4'>                        </td>                    </tr>                    <?php                    $c++;                }                ?>                <tfoot>                    <tr>                        <td colspan="5">                            <div class="pagination">                                <div class="limit">                                    <input type='hidden' name='action' value='0' />                                    <?php echo $this->pagination->getList(); ?>                                    <?php echo $this->pagination->getPagesCounter(); ?>                                </div>                            </div>                        </td>                    </tr>                </tfoot>            </table>        </div></div>