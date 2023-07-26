import { ref, watch } from "vue";

export function useTableQuery(callbackFn = null) {
  const query = ref({
    q: null,
    orderBy: null,
    order: null,
    page: 1,
    limit: 10,
  });
  const pagination = ref({
    currentPage: 1,
  });
  const timeout = ref(null);

  watch(
    () => query.value.q,
    () => search()
  );

  watch(
    () => query.value.limit,
    () => changeElementsPerPage()
  );

  function search() {
    if (timeout.value) clearTimeout(timeout.value);
    timeout.value = setTimeout(() => {
      updateQuery(query.value.q, 1, query.value.orderBy, query.value.order, query.value.limit);
    }, 200);
  }

  async function orderBy(column) {
    let order = "DESC";

    if (query.value.orderBy === column) {
      order = query.value.order === "ASC" ? "DESC" : "ASC";
    }

    await updateQuery(query.value.q, query.value.page, column, order, query.value.limit);
  }

  async function changePage(page) {
    await updateQuery(
      query.value.q,
      page,
      query.value.orderBy,
      query.value.order,
      query.value.limit
    );
  }

  async function changeElementsPerPage() {
    await updateQuery(query.value.q, 1, query.value.orderBy, query.value.order, query.value.limit);
  }

  async function updateQuery(input, page, orderBy, order, limit) {
    query.value = {
      q: input || null,
      page: parseInt(page),
      orderBy: orderBy,
      order: order,
      limit: parseInt(limit),
    };

    await callbackFn(query.value).then(resp => makePagination(resp.meta, resp.links));
  }

  function makePagination(meta, links) {
    let startPage = meta.current_page - 2 < 1 ? 1 : meta.current_page - 2;
    let pageToRender = [];

    for (let i = startPage, j = 0; i <= meta.last_page && j < 5; i++, j++) {
      pageToRender.push(i);
    }

    if (meta.last_page > 5 && pageToRender.length < 5) {
      for (let i = pageToRender[0] - 1; pageToRender.length < 5 && i > 1; i--) {
        pageToRender.unshift(i);
      }
    }

    pagination.value = {
      currentPage: meta.current_page,
      lastPage: meta.last_page,
      nextPageUrl: links.next,
      prevPageUrl: links.prev,
      pagesToRender: pageToRender,
    };
  }

  return { orderBy, changePage, makePagination, pagination, query };
}
