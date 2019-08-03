$(function(){
  var txtRender = $.fn.dataTable.render.text(),
    numberRender = $.fn.dataTable.render.number(',', '.', 0);
  $('#questions-list').DataTable({
    ajax: "/admin/questions/datatable?cb=" + Math.floor(Math.random() * 1e6) + document.location.search.replace(/^\?/, '&'),
    dom: 'T<"clear">lfrtip',
    processing: true,
    serverSide: true,
    order: [[0, 'desc']],
    columns: [
      {
        data: 'id',
        width: 100,
        render: txtRender
      },
      {
        data: 'question',
        width: 'auto',
        render: txtRender
      },
      {
        data: 'type_id',
        width: 'auto',
        render: function(t){
          return typeof window.questionTypes[t] == 'string' ? window.questionTypes[t] : '';
        }
      },
      {
        data: 'point_id',
        render: function(id, type, row, meta){
          return id > 0 ? '<a href="/admin/points/edit/' + id + '">' + txtRender.display(row.name) + '</a>' : '';
        }
      },
      {
        data: 'score',
        width: 50,
        render: numberRender
      },
      {
        orderable: false,
        searchable: false,
        data: 'hunt_points',
        width: 90,
        render: numberRender
      },
      {
        orderable: false,
        searchable: false,
        data: 'answers',
        width: 90,
        render: function(data, type, row, meta) {
          return '<a href="/admin/questions/submittion/' + row.id + '">' + numberRender.display(data) + '</a>';
        }
        // render: numberRender
      },
      {
        searchable: false,
        data: 'wrong_answers',
        width: 80,
        render: function(data, type, row, meta) {
          return '<a href="/admin/questions/wrong/' + row.id + '">' + numberRender.display(data) + '</a>';
        }
      },
      {
        data: 'tag',
        render: function(data, type, row) {
          return txtRender.display(row.tags);
        }
      },
      {
        orderable: false,
        searchable: false,
        width: 300,
        render: function(data, type, row, meta){
          return '<a href="/admin/questions/duplicate/' + row.id + '" class="btn btn-success" onclick="return confirm(\'Are you sure?\')">Duplicate</a> ' +
              '<a href="/admin/questions/edit/' + row.id + '" class="btn btn-warning">Edit</a> ' +
              '<a href="/admin/questions/delete/' + row.id + '" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to delete this question?\')">Delete</a>';
        }
      },
      {
        orderable: false,
        searchable: false,
        visible: false,
        data: 'tags'
      }
    ],
    tableTools: {
      sSwfPath: "/template/js/plugins/tabletools/swf/copy_csv_xls_pdf.swf",
      aButtons: [
        {
          "sExtends": "csv",
          "mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
        },
        {
          "sExtends": "xls",
          "mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
        },
        {
          "sExtends": "pdf",
          "mColumns": [0, 1, 2, 3, 4, 5, 6, 7]
        },
        {
          "sExtends": "print",
          "mColumns": [0, 1, 2, 3, 4, 5, 6]
        }
      ]
    }
  });
});