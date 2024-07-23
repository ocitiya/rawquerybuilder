class Helper:
    def inList(self, lst, element):
        try:
          lst.index(element)
          return True
        except ValueError:
          return False
