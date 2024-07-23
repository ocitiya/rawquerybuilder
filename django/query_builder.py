# MIT License

# Copyright (c) 2023 Muhammad Rasyidi
# https://github.com/ocitiya/rawquerybuilder

from .db import DB
from .helper import Helper

import copy

helper = Helper()

class QueryBuilder:
  def __init__(self, table, params=[]):
    self.table = table
    self._ct = []
    self._sl = {"query": [], "params": []}
    self._fr = "FROM " + table
    self._lj = []
    self._jn = []
    self._wh = {"query": None, "params": []}
    self._od = []
    self._lm = None
    self._gr = []

    self.dbs = db()

    for p in params:
      self._wh['params'].append(p)

  def froms(self, query):
    self._fr += ", " + query

  def cte(self, variable, query, params = []):
    self._ct.append({"variable": variable, "query": query})
    for p in params:
      self._wh['params'].append(p)

  def select(self, lists, params = []):
    if type(lists) == list:
      for item in lists:
        if not helper.inList(self._sl, item):
          self._sl['query'].append(item)
    else:
      if not helper.inList(self._sl, lists):
          self._sl['query'].append(lists)
    
    for p in params:
      self.sl['params'].append(p)

    return self

  def where(self, text, params = []):
    if self._wh['query'] is None:
      self._wh['query'] = f"WHERE {text} "
    else:
      self._wh['query'] += f"AND {text} "
    
    if (isinstance(params, list)):
      for p in params:
        self._wh['params'].append(p)
    else:
      self._wh['params'].append(params)

    return self

  def orWhere(self, text, params = []):
    if self._wh['query'] is None:
      self._wh['query'] = f"WHERE {text} "
    else:
      self._wh['query'] += f"OR {text} "

    for p in params:
      self._wh['params'].append(p)

    return self
  
  def leftJoin(self, lists, params=[]):
    if type(lists) == list:
      for item in lists:
        query = f"LEFT JOIN {item}"
        if not helper.inList(self._lj, query):
          self._lj.append(query)
    else:
      query = f"LEFT JOIN {lists}"
      if not helper.inList(self._lj, query):
        self._lj.append(query)

    for p in params:
      self._wh['params'].append(p)
    
    return self
  
  def join(self, lists, params=[]):
    if type(lists) == list:
      for item in lists:
        query = f"JOIN {item}"
        if not helper.inList(self._jn, query):
          self._jn.append(query)
    else:
      query = f"JOIN {lists}"
      if not helper.inList(self._jn, query):
        self._jn.append(query)

    for p in params:
      self._wh['params'].append(p)
    
    return self
  
  def order(self, lists):
    if type(lists) == list:
      for item in lists:
        if not helper.inList(self._od, item):
          self._od.append(item)
    else:
      if not helper.inList(self._od, lists):
        self._od.append(lists)

  def group(self, lists):
    if type(lists) == list:
      for item in lists:
        if not helper.inList(self._gr, item):
          self._gr.append(item)
    else:
      if not helper.inList(self._gr, lists):
        self._gr.append(lists)
    
    return self

  def limit(self, lim):
    self._lm = lim

  def __generateSql(self):
    params = []
    query = ""

    if len(self._ct) > 0:
      query += "WITH "
      for (i, c) in enumerate(self._ct):
        query += c["variable"] + " AS " + "(" + c["query"] + ")"
        if i < len(self._ct) - 1: query += ", "
        else: query += " "

    selects = "*"
    if len(self._sl['query']) > 0: selects = ", ".join(self._sl['query'])
    query += "SELECT " + selects + " " + self._fr + " "
    for p in self.sl['params']:
      params.append(p)

    if len(self._jn) > 0:
      joins = " ".join(self._jn)
      query += joins + " "

    if len(self._lj) > 0:
      leftJoins = " ".join(self._lj)
      query += leftJoins + " "

    if self._wh['query'] is not None:
      query += self._wh['query'] + " "
    
    for p in self._wh['params']:
        params.append(p)

    if len(self._gr) > 0:
      groups = ", ".join(self._gr)
      query += f"GROUP BY {groups} "

    if len(self._od) > 0:
      orders = ", ".join(self._od)
      query += f"ORDER BY {orders} "

    if self._lm is not None:
      query += f"LIMIT {self._lm}"

    return { 'raw': query, 'params': params }

  def get(self):
    sql = self.__generateSql()

    data = self.dbs.execute(sql['raw'], sql['params'])
    return data
  
  def first(self):
    self._lm = 1
    sql = self.__generateSql()

    data = self.dbs.execute(sql['raw'], sql['params'])
    if len(data) == 0: return None
    else: return data[0]

  def count(self, column=None):
    if column is not None: self._sl = ["COUNT() AS total"]
    else: self._sl = ["COUNT(*) AS total"]

    sql = self.__generateSql()
    data = self.dbs.execute(sql['raw'], sql['params'])
    return data[0]["total"]

  def toSql(self):
    return self.__generateSql()
  
  def clone(self):
    new_instance = QueryBuilder(self.table)

    for key, value in self.__dict__.items():
      if key == '_sl':
        new_instance.__dict__['_sl'] = {
          "query": self._sl['query'][:],
          "params": self._sl['params'][:]
        }
      elif key == '_wh':
        new_instance.__dict__['_wh'] = {
          "query": self._wh['query'][:],
          "params": self._wh['params'][:]
        }
      else:
        new_instance.__dict__[key] = copy.copy(value)

    return new_instance

  
