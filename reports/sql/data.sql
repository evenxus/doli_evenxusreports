-- ===================================================================
-- Copyright (C) 2011-2012 Juanjo Menent <jmenent@2byte.es>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program; if not, write to the Free Software
-- Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
--
-- ===================================================================
TRUNCATE TABLE `llx_reports_group`;

INSERT INTO `llx_reports_group` (`rowid`, `entity`, `code`, `name`, `active`) VALUES
(1, 1, '0000', 'noAssigned', 1),
(2, 1, 'FINA', 'Financial', 1),
(6, 1, 'PROD', 'Products', 1),
(7, 1, 'COMM', 'Commercial', 1),
(8, 1, 'TIER', 'Third', 1);

TRUNCATE TABLE `llx_reports_report`;

INSERT INTO `llx_reports_report` (`rowid`, `entity`, `code`, `fk_group`, `name`, `xmlin`, `active`) VALUES
(1, 1, 'P001', 6, 'Products', 'Products.xml', 1),
(2, 1, 'P002', 6, 'Stocks', 'Stocks.xml', 1),
(3, 1, 'P003', 6, 'Stock alerts', 'Stock_Alerts.xml', 1),
(4, 1, 'P004', 6, 'Products sell', 'Product_Sales.xml', 1),
(5, 1, 'P005', 2, 'Customer invoices', 'Invoices.xml', 1),
(6, 1, 'P006', 7, 'Customer proposals', 'Prospects.xml', 1),
(7, 1, 'P007', 7, 'Customer orders', 'Orders.xml', 1),
(8, 1, 'P008', 8, 'Thirds', 'Thirds.xml', 1),
(9, 1, 'P009', 8, 'Contacts', 'Contacts.xml', 1),
(10, 1, 'P010', 2, 'Sales summary', 'Stats_Invoice.xml', 1),
(11, 1, 'P011', 2, 'Supplier invoices', 'Supplier_Invoices.xml', 1),
(12, 1, 'P012', 2, 'Purchases summary', 'Stats_Invoice_Supplier.xml', 1),
(13, 1, 'P013', 6, 'Sales details', 'Sales_Details.xml', 1),
(14, 1, 'P014', 7, 'Supplier orders', 'Provider_Orders.xml', 1),
(15, 1, 'P015', 2, 'Sales Tax detail', 'Sales_Vat.xml', 1),
(16, 1, 'P016', 2, 'Buys Tax detail', 'Buys_Vat.xml', 1);