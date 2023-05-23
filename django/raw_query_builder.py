# MIT License

# Copyright (c) 2023 Muhammad Rasyidi
# https://github.com/ocitiya/rawquerybuilder.git

# Permission is hereby granted, free of charge, to any person obtaining a copy
# of this software and associated documentation files (the "Software"), to deal
# in the Software without restriction, including without limitation the rights
# to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
# copies of the Software, and to permit persons to whom the Software is
# furnished to do so, subject to the following conditions:

# The above copyright notice and this permission notice shall be included in all
# copies or substantial portions of the Software.

# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
# IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
# FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
# AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
# LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
# OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
# SOFTWARE.


from services.db import db
from .helper import Helper

helper = Helper()

class QueryBuilder:
  def __init__(self, table):
    self.sl = []
    self.fr = "FROM " + table
    self.lj = []
    self.wh = {"query": None, "params": []}
    self.od = []
    self.lm = None
    self.gr = []

  def select(self, lists, params = []):
    if type(lists) == list:
      for item in lists:
        if not helper.inList(self.sl, item):
          self.sl.append(item)
    else:
      if not helper.inList(self.sl, lists):
          self.sl.append(lists)
    
    for p in params:
      self.wh['params'].append(p)

    return self

  def where(self, text, params = []):
    if self.wh['query'] is None:
      self.wh['query'] = f"WHERE {text} "
    else:
      self.wh['query'] += f"AND {text} "
    
    for p in params:
      self.wh['params'].append(p)

    return self

  def orWhere(self, text, params = []):
    if self.wh['query'] is None:
      self.wh['query'] = f"WHERE {text} "
    else:
      self.wh['query'] += f"OR {text} "

    for p in params:
      self.wh['params'].append(p)

    return self
  
  def leftJoin(self, lists, params=[]):
    if type(lists) == list:
      for item in lists:
        query = f"LEFT JOIN {item}"
        if not helper.inList(self.lj, query):
          self.lj.append(query)
    else:
      query = f"LEFT JOIN {lists}"
      if not helper.inList(self.lj, query):
        self.lj.append(query)

    for p in params:
      self.wh['params'].append(p)
    
    return self
  
  def order(self, lists):
    if type(lists) == list:
      for item in lists:
        if not helper.inList(self.od, item):
          self.od.append(item)
    else:
      if not helper.inList(self.od, lists):
        self.od.append(lists)

  def group(self, lists):
    if type(lists) == list:
      for item in lists:
        if not helper.inList(self.gr, item):
          self.gr.append(item)
    else:
      if not helper.inList(self.gr, lists):
        self.gr.append(lists)
    
    return self

  def __generateSql(self):
    params = []
    
    selects = ", ".join(self.sl)
    query = "SELECT " + selects + " " + self.fr + " "

    if len(self.lj) > 0:
      leftJoins = " ".join(self.lj)
      query += leftJoins + " "

    if self.wh['query'] is not None:
      query += self.wh['query'] + " "
      for p in self.wh['params']:
        params.append(p)

    if len(self.gr) > 0:
      groups = ", ".join(self.gr)
      query += f"GROUP BY {groups} "

    if len(self.od) > 0:
      orders = ", ".join(self.od)
      query += f"ORDER BY {orders} "

    return { 'raw': query, 'params': params }

  def get(self):
    sql = self.__generateSql()

    data = db.execute(sql['raw'], sql['params'])
    return data
  
  def first(self):
    self.lm = 1
    sql = self.__generateSql()

    data = db.execute(sql['raw'], sql['params'])
    if len(data == 0): return None
    else: return data[0]

  def count(self, column=None):
    if column is not None: self.sl = ["COUNT() AS total"]
    else: self.sl = ["COUNT(*) AS total"]

    sql = self.__generateSql()
    data = db.execute(sql['raw'], sql['params'])
    return data[0]["total"]

  def toSql(self):
    return self.__generateSql()
  