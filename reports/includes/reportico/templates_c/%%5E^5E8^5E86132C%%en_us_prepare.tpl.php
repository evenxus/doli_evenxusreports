<?php /* Smarty version 2.6.26, created on 2013-03-14 10:19:46
         compiled from en_us_prepare.tpl */ ?>
<div class="fiche">
<div class="blockvmenupair">
<div class="menu_titre"><a href="/dolibarr/htdocs/product/index.php?leftmenu=product&amp;type=0" class="vmenu"><?php echo $this->_tpl_vars['TITLE']; ?>
</a></div>

</div>	
	
<?php if (strlen ( $this->_tpl_vars['ERRORMSG'] ) > 0): ?> 
			<TABLE class="swError">
				<TR>
					<TD><?php echo $this->_tpl_vars['ERRORMSG']; ?>
</TD>
				</TR>
			</TABLE>
<?php endif; ?>
<FORM name="topmenu" method="POST" action="<?php echo $this->_tpl_vars['SCRIPT_SELF']; ?>
">
<input type="hidden" name="session_name" value="<?php echo $this->_tpl_vars['SESSION_ID']; ?>
" />
<?php if ($this->_tpl_vars['SHOW_TOPMENU']): ?>
	<TABLE width="100%" style="display:none">
		<TR>
<?php if (( $this->_tpl_vars['DB_LOGGEDON'] )): ?> 
			<TD style="width: 15%" class="swPrpTopMenuCell">
<?php if (( $this->_tpl_vars['DBUSER'] )): ?>
Logged On As <?php echo $this->_tpl_vars['DBUSER']; ?>

<?php else: ?>
&nbsp;
<?php endif; ?>
			</TD>
<?php endif; ?>
<?php if (strlen ( $this->_tpl_vars['MAIN_MENU_URL'] ) > 0): ?> 
			<TD style="text-align:center"><a class="swLinkMenu" href="<?php echo $this->_tpl_vars['MAIN_MENU_URL']; ?>
">Project Menu</a></TD>
<?php endif; ?>
<?php if ($this->_tpl_vars['SHOW_MODE_BOX']): ?>
			<TD width="30%">
				<input class="swMntButton" type="submit" name="submit_design_mode" value="Design Report">
			</TD>
<?php endif; ?>
<?php if ($this->_tpl_vars['SHOW_LOGOUT']): ?>
			<TD width="15%" align="right" class="swPrpTopMenuCell">
				<input class="swPrpSubmit" type="submit" name="logout" value="Log Off">
			</TD>
<?php endif; ?>
<?php if ($this->_tpl_vars['SHOW_LOGIN']): ?>
			<TD width="50%"></TD>
			<TD width="35%" align="right" class="swPrpTopMenuCell">
				User Id <input type="text" name="userid" value=""><br>
				Password <input type="password" name="password" value="">
			</TD>
			<TD width="15%" align="right" class="swPrpTopMenuCell">
				<input class="swPrpSubmit" type="submit" name="login" value="Login">
			</TD>
<?php endif; ?>
		</TR>
	</TABLE>
<?php endif; ?>
<?php if ($this->_tpl_vars['SHOW_CRITERIA']): ?>
	<TABLE class="" style="width:100%" cellpadding="0">
<!---->
<?php if ($this->_tpl_vars['SHOW_OUTPUT']): ?>
					<tr>
						<td colspan=3>
							<TABLE class="noborder" style="width:100%">
								<TR class="liste_titre">
									<td width="20%">
										&nbsp;
										<input type="submit" class="swPrpSubmit" name="submit" value="Execute">
										<input type="submit" class="swPrpSubmit" name="clearform" value="Reset">
									</TD>
									<TD width="40%" style="vertical-align: top">
										&nbsp;
										Output :
											<INPUT type="radio" name="target_format" value="HTML" <?php echo $this->_tpl_vars['OUTPUT_TYPES'][0]; ?>
>HTML
											<INPUT type="radio" name="target_format" value="PDF" <?php echo $this->_tpl_vars['OUTPUT_TYPES'][1]; ?>
>PDF
											<INPUT type="radio" name="target_format" value="CSV" <?php echo $this->_tpl_vars['OUTPUT_TYPES'][2]; ?>
>CSV
									<td width="40%" style="vertical-align: top">
										<!--INPUT type="checkbox" name="target_attachment" value="1" <?php echo $this->_tpl_vars['OUTPUT_ATTACH']; ?>
>As Attachment</INPUT-->
										&nbsp;
										Show 
										<INPUT type="checkbox" name="target_show_criteria" value="1" <?php echo $this->_tpl_vars['OUTPUT_SHOWCRITERIA']; ?>
>Criteria</INPUT>
										<INPUT type="checkbox" name="target_show_body" value="1" <?php echo $this->_tpl_vars['OUTPUT_SHOWDET']; ?>
>Detail</INPUT>
<?php if ($this->_tpl_vars['OUTPUT_SHOW_SHOWGRAPH']): ?>
										<INPUT type="checkbox" name="target_show_graph" value="1" checked>Graph</INPUT><BR>
<?php endif; ?>
									</td>
								</TR>
							</TABLE>
						</td>
					</tr>
<?php else: ?>
					<tr>
						<td class="" colspan=3>
							<TABLE class="noborder" width="100%">
								<TR class="liste_titre">
									<td width="25%">
										&nbsp;
										<input type="submit" class="swPrpSubmit" name="submit" value="Execute">
										<input type="submit" class="swPrpSubmit" name="clearform" value="Reset">
									</TD>
								</TR>
							</TABLE>
						</td>
					</tr>
<?php endif; ?>
		<TR>
			<TD style="width:50%">
				<TABLE class="" style="width:100%">
<?php unset($this->_sections['critno']);
$this->_sections['critno']['name'] = 'critno';
$this->_sections['critno']['loop'] = is_array($_loop=$this->_tpl_vars['CRITERIA_ITEMS']) ? count($_loop) : max(0, (int)$_loop); unset($_loop);
$this->_sections['critno']['show'] = true;
$this->_sections['critno']['max'] = $this->_sections['critno']['loop'];
$this->_sections['critno']['step'] = 1;
$this->_sections['critno']['start'] = $this->_sections['critno']['step'] > 0 ? 0 : $this->_sections['critno']['loop']-1;
if ($this->_sections['critno']['show']) {
    $this->_sections['critno']['total'] = $this->_sections['critno']['loop'];
    if ($this->_sections['critno']['total'] == 0)
        $this->_sections['critno']['show'] = false;
} else
    $this->_sections['critno']['total'] = 0;
if ($this->_sections['critno']['show']):

            for ($this->_sections['critno']['index'] = $this->_sections['critno']['start'], $this->_sections['critno']['iteration'] = 1;
                 $this->_sections['critno']['iteration'] <= $this->_sections['critno']['total'];
                 $this->_sections['critno']['index'] += $this->_sections['critno']['step'], $this->_sections['critno']['iteration']++):
$this->_sections['critno']['rownum'] = $this->_sections['critno']['iteration'];
$this->_sections['critno']['index_prev'] = $this->_sections['critno']['index'] - $this->_sections['critno']['step'];
$this->_sections['critno']['index_next'] = $this->_sections['critno']['index'] + $this->_sections['critno']['step'];
$this->_sections['critno']['first']      = ($this->_sections['critno']['iteration'] == 1);
$this->_sections['critno']['last']       = ($this->_sections['critno']['iteration'] == $this->_sections['critno']['total']);
?>
					<tr>
						<td>
							<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['title']; ?>

						</td>
						<td>
							<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['entry']; ?>

						</td>
						<td class="swPrpCritExpandSel">
<?php if ($this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['expand']): ?>
							<input class="swPrpCritExpandButton" type="submit" name="EXPAND_<?php echo $this->_tpl_vars['CRITERIA_ITEMS'][$this->_sections['critno']['index']]['name']; ?>
" value=">>">
<?php endif; ?>
						</td>
					</TR>
<?php endfor; endif; ?>
				</TABLE>
			</td>
			<TD class="">
				<TABLE class="swPrpExpandBox">
					<TR class="swPrpExpandRow">
						<TD class="swPrpExpandCell" rowspan="0" valign="top">
<?php if ($this->_tpl_vars['SHOW_EXPANDED']): ?>
							Search <?php echo $this->_tpl_vars['EXPANDED_TITLE']; ?>
 :<br><input type="text" name="expand_value" size="30" value="<?php echo $this->_tpl_vars['EXPANDED_SEARCH_VALUE']; ?>
">
									<input class="swPrpSubmit" type="submit" name="EXPANDSEARCH_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="Search"><br>

<?php echo $this->_tpl_vars['CONTENT']; ?>

							<br>
							<input class="swPrpSubmit" type="submit" name="EXPANDCLEAR_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="Clear">
							<input class="swPrpSubmit" type="submit" name="EXPANDSELECTALL_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="Select All">
							<input class="swPrpSubmit" type="submit" name="EXPANDOK_<?php echo $this->_tpl_vars['EXPANDED_ITEM']; ?>
" value="OK">
<?php endif; ?>
<?php if (! $this->_tpl_vars['SHOW_EXPANDED']): ?>
<div class="info">
<?php if (! $this->_tpl_vars['REPORT_DESCRIPTION']): ?>
						Enter Your Report Criteria Here. To enter criteria use the appropriate expand key.
						When you are happy select the appropriate output format and click OK.
<?php else: ?>
						<?php echo $this->_tpl_vars['REPORT_DESCRIPTION']; ?>

<?php endif; ?>
</div>
<?php endif; ?>
						</TD>
					</TR>
				</TABLE>
			</TD>
		</TR>
								<tr class="liste_titre">
									<td colspan=2 class="">
										&nbsp;
										<input type="submit" class="swPrpSubmit" name="submit" value="Execute">
										<input type="submit" class="swPrpSubmit" name="clearform" value="Reset">
									</TD>
								</TR>
			</TABLE>

<?php endif; ?>
			<!---->
<?php if (strlen ( $this->_tpl_vars['STATUSMSG'] ) > 0): ?> 
			<TABLE class="swStatus">
				<TR>
					<TD><?php echo $this->_tpl_vars['STATUSMSG']; ?>
</TD>
				</TR>
			</TABLE>
<?php endif; ?>
		</TR>
	</TABLE>
</FORM>
</div>