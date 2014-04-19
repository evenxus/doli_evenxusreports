
<script language="JavaScript">

function disableEnterKey(e)
{ldelim}
     var key;     
     if(window.event)
          key = window.event.keyCode; //IE
     else
          key = e.which; //firefox     

     return (key != 13);
{rdelim}

</script>

<div class="fiche">

<div class="blockvmenupair">
<div class="menu_titre">{$TITLE}</div>

</div>	
	
{if strlen($ERRORMSG)>0} 
			<TABLE class="swError">
				<TR>
					<TD>{$ERRORMSG}</TD>
				</TR>
			</TABLE>
{/if}
<FORM name="topmenu" method="POST" action="{$SCRIPT_SELF}" >
<input type="hidden" name="session_name" value="{$SESSION_ID}" />
{if $SHOW_TOPMENU}
	<TABLE width="100%" style="display:none">
		<TR>
{if ($DB_LOGGEDON)} 
			<TD style="width: 15%" class="swPrpTopMenuCell">
{if ($DBUSER)}
Logged On As {$DBUSER}
{else}
&nbsp;
{/if}
			</TD>
{/if}
{if strlen($MAIN_MENU_URL)>0} 
			<TD style="text-align:center"><a class="swLinkMenu" href="{$MAIN_MENU_URL}">Project Menu</a></TD>
{/if}
{if $SHOW_MODE_BOX}
			<TD width="30%">
				<input class="swMntButton" type="submit" name="submit_design_mode" value="Design Report">
			</TD>
{/if}
{if $SHOW_LOGOUT}
			<TD width="15%" align="right" class="swPrpTopMenuCell">
				<input class="swPrpSubmit" type="submit" name="logout" value="Log Off">
			</TD>
{/if}
{if $SHOW_LOGIN}
			<TD width="50%"></TD>
			<TD width="35%" align="right" class="swPrpTopMenuCell">
				User Id <input type="text" name="userid" value=""><br>
				Password <input type="password" name="password" value="">
			</TD>
			<TD width="15%" align="right" class="swPrpTopMenuCell">
				<input class="swPrpSubmit" type="submit" name="login" value="Login">
			</TD>
{/if}
		</TR>
	</TABLE>
{/if}
{if $SHOW_CRITERIA}
	<TABLE class="" style="width:100%" cellpadding="0">
<!---->
{if $SHOW_OUTPUT}
					<tr>
						<td colspan=3>
							<TABLE class="noborder" style="width:100%">
								<TR class="liste_titre">
									<td width="20%">
										&nbsp;
										<input type="submit" class="swPrpSubmit" name="submit" value="Ejecutar">
										<input type="submit" class="swPrpSubmit" name="clearform" value="Resetear">
									</TD>
									<TD width="40%" style="vertical-align: top">
										&nbsp;
										Salida :
											<INPUT type="radio" name="target_format" value="HTML" {$OUTPUT_TYPES[0]}>HTML
											<INPUT type="radio" name="target_format" value="PDF" {$OUTPUT_TYPES[1]}>PDF
											<INPUT type="radio" name="target_format" value="CSV" {$OUTPUT_TYPES[2]}>CSV
									<td width="40%" style="vertical-align: top">
										<!--INPUT type="checkbox" name="target_attachment" value="1" {$OUTPUT_ATTACH}>As Attachment</INPUT-->
										&nbsp;
										Ver 
										<INPUT type="checkbox" name="target_show_criteria" value="1" {$OUTPUT_SHOWCRITERIA}>Criterios</INPUT>
										<INPUT type="checkbox" name="target_show_body" value="1" {$OUTPUT_SHOWDET}>Detalles</INPUT>
{if $OUTPUT_SHOW_SHOWGRAPH}
										<INPUT type="checkbox" name="target_show_graph" value="1" checked>Gráficos</INPUT><BR>
{/if}
									</td>
								</TR>
							</TABLE>
						</td>
					</tr>
{else}
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
{/if}
		<TR>
			<TD style="width:50%">
				<TABLE class="" style="width:100%">
{section name=critno loop=$CRITERIA_ITEMS}
					<tr>
						<td>
							{$CRITERIA_ITEMS[critno].title}
						</td>
						<td>
							{$CRITERIA_ITEMS[critno].entry}
						</td>
						<td class="swPrpCritExpandSel">
{if $CRITERIA_ITEMS[critno].expand}
							<input class="swPrpCritExpandButton" type="submit" name="EXPAND_{$CRITERIA_ITEMS[critno].name}" value=">>">
{/if}
						</td>
					</TR>
{/section}
				</TABLE>
			</td>
			<TD class="">
				<TABLE class="swPrpExpandBox">
					<TR class="swPrpExpandRow">
						<TD class="swPrpExpandCell" rowspan="0" valign="top">
{if $SHOW_EXPANDED}
							Buscar {$EXPANDED_TITLE} :<br><input type="text" name="expand_value" size="30" value="{$EXPANDED_SEARCH_VALUE}" onKeyPress=”return disableEnterKey(event)”>>
									<input class="swPrpSubmit" type="submit" name="EXPANDSEARCH_{$EXPANDED_ITEM}" value="Buscar"><br>

{$CONTENT}
							<br>
							<input class="swPrpSubmit" type="submit" name="EXPANDCLEAR_{$EXPANDED_ITEM}" value="Limpiar">
							<input class="swPrpSubmit" type="submit" name="EXPANDSELECTALL_{$EXPANDED_ITEM}" value="Todo">
							<input class="swPrpSubmit" type="submit" name="EXPANDOK_{$EXPANDED_ITEM}" value="OK">
{/if}
{if !$SHOW_EXPANDED}
<div class="info">
{if !$REPORT_DESCRIPTION}
						Introduzca los criterios del informe aquí. Para introducir los criterios use las claves apropiadas.
						Cuando esté de acuerdo selecciones el formato de salida correspondiente y haga clic en Ejecutar.
{else}
						{$REPORT_DESCRIPTION}
{/if}
</div>
{/if}
						</TD>
					</TR>
				</TABLE>
			</TD>
		</TR>
								<tr class="liste_titre">
									<td colspan=2 class="">
										&nbsp;
										<input type="submit" class="swPrpSubmit" name="submit" value="Ejecutar">
										<input type="submit" class="swPrpSubmit" name="clearform" value="Resetear">
									</TD>
								</TR>
			</TABLE>

{/if}
			<!---->
{if strlen($STATUSMSG)>0} 
			<TABLE class="swStatus">
				<TR>
					<TD>{$STATUSMSG}</TD>
				</TR>
			</TABLE>
{/if}
		</TR>
	</TABLE>
</FORM>
</div>
