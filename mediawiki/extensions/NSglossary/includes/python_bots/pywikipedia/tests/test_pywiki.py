#!/usr/bin/python
# -*- coding: utf-8  -*-

"""Unit test framework for pywiki"""
__version__ = '$Id: test_pywiki.py 11459 2013-04-26 18:06:07Z drtrigon $'

import unittest
import test_utils

import wikipedia as pywikibot


class PyWikiTestCase(unittest.TestCase):

    def setUp(self):
        self.site = pywikibot.getSite('en', 'wikipedia')

    def _check_member(self, obj, member, call=False):
        self.assertTrue( hasattr(obj, member) )
        if call:
            self.assertTrue( callable(getattr(obj, member)) )

if __name__ == "__main__":
    unittest.main()
