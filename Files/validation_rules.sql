-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: May 02, 2012 at 11:51 PM
-- Server version: 5.1.57
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `web130-ee-2`
--

-- --------------------------------------------------------

--
-- Table structure for table `exp_custom_form_validation_rules`
--

CREATE TABLE IF NOT EXISTS `exp_custom_form_validation_rules` (
  `rule_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(255) DEFAULT NULL,
  `rule_use_captcha` tinyint(1) DEFAULT '0',
  `rule_fields` longtext,
  `rule_date_created` datetime DEFAULT NULL,
  `rule_date_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`rule_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10 ;

--
-- Dumping data for table `exp_custom_form_validation_rules`
--

INSERT INTO `exp_custom_form_validation_rules` (`rule_id`, `rule_name`, `rule_use_captcha`, `rule_fields`, `rule_date_created`, `rule_date_modified`) VALUES
(8, 'Test', 1, '[{"field":"name","label":"Name","rules":"required","recipientEmail":false,"type":"text","value":"Full Name","accept":"i"},{"field":"email","label":"Email Address","rules":"required|valid_email|max_length[255]","recipientEmail":true,"type":"text","value":"Email"},{"field":"file","label":"Attachment","rules":"required","recipientEmail":false,"type":"file","value":"","accept":"image\\/*"},{"field":"password","label":"Password","rules":"alpha_dash|min_length[6]","recipientEmail":false,"type":"password","value":"Password"},{"field":"checkbox","label":"Day","rules":"","recipientEmail":false,"type":"checkbox","value":"Monday:mon,Tuesday:tue"},{"field":"dropdown","label":"Drop-down","rules":"","recipientEmail":false,"type":"select","value":"Opt 1:1,Opt 2:2"},{"field":"comments","label":"Enquiry","rules":"min_length[10]|max_length[50]","recipientEmail":false,"type":"textarea","value":"Enter your Enquiry here..."}]', '2012-03-15 23:07:10', '2012-04-21 20:20:30'),
(9, 'file_upload', 0, '[{"field":"fileUpload","label":"Profile Image","rules":"required","recipientEmail":false,"type":"file","value":""}]', '2012-04-16 12:00:31', '2012-05-02 19:36:22');

