/**
 * 积分任务
 * 对应 yxshop-php: /api/v1/point/tasks/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  // 任务列表
  getList: () => get('/api/v1/point/tasks'),

  // 领取任务奖励
  claim: (taskId) => post('/api/v1/point/tasks/claim', { task_id: taskId }),
};
