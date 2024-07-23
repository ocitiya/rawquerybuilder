from django.db import connection

class DB:
  def __init__(self):
    self.cursor = connection.cursor()

  def set(self, query, tuple=()):
    self.cursor.execute(f"SET {query};", tuple)

  def execute(self, query, tuple=()):
    self.cursor.execute(query, tuple)
    row = [dict((self.cursor.description[i][0], value) for i, value in enumerate(row)) for row in self.cursor.fetchall()]
    return row
