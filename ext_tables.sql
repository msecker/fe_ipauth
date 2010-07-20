#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_feipauth_ip_allow text NOT NULL,
	tx_feipauth_ip_deny text NOT NULL
);



#
# Table structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
	tx_feipauth_ip_allow text NOT NULL,
	tx_feipauth_ip_deny text NOT NULL
);


#
# Table structure for table 'tx_feipauth_ipcache'
#
CREATE TABLE tx_feipauth_ipcache (
	user_id int(11) unsigned DEFAULT '0' NOT NULL,
	group_id int(11) unsigned DEFAULT '0' NOT NULL,
	rule_type tinyint(3) unsigned DEFAULT '0' NOT NULL,

	is_v6 tinyint(3) unsigned DEFAULT '0' NOT NULL,

	address_0 int(11) unsigned default '0' NOT NULL,
	address_1 int(11) unsigned default '0' NOT NULL,
	address_2 int(11) unsigned default '0' NOT NULL,
	address_3 int(11) unsigned default '0' NOT NULL,

	netmask_0 int(11) unsigned default '0' NOT NULL,
	netmask_1 int(11) unsigned default '0' NOT NULL,
	netmask_2 int(11) unsigned default '0' NOT NULL,
	netmask_3 int(11) unsigned default '0' NOT NULL,

	network_0 int(11) unsigned default '0' NOT NULL,
	network_1 int(11) unsigned default '0' NOT NULL,
	network_2 int(11) unsigned default '0' NOT NULL,
	network_3 int(11) unsigned default '0' NOT NULL,

	host_0 int(11) unsigned default '0' NOT NULL,
	host_1 int(11) unsigned default '0' NOT NULL,
	host_2 int(11) unsigned default '0' NOT NULL,
	host_3 int(11) unsigned default '0' NOT NULL,
);


